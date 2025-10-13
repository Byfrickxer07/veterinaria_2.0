<?php
session_start();

try {
    $conn = new PDO("mysql:host=localhost;dbname=veterinaria", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Actualizar automáticamente a 'Terminado' los turnos pendientes con fecha/hora pasada
    $auto = $conn->prepare("UPDATE turnos 
                            SET estado = 'Terminado'
                            WHERE user_id = :uid
                              AND estado = 'Pendiente'
                              AND (fecha < CURDATE() OR (fecha = CURDATE() AND hora < CURTIME()))");
    $auto->execute([':uid' => $user_id]);

    // Manejo de acciones POST: editar/eliminar turno
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id'])) {
        $accion = $_POST['accion'];
        $id = (int)$_POST['id'];

        if ($accion === 'eliminar') {
            $del = $conn->prepare("DELETE FROM turnos WHERE id = :id AND user_id = :uid");
            $del->execute([':id' => $id, ':uid' => $user_id]);
            header("Location: ver_turnos.php");
            exit();
        }

        if ($accion === 'editar') {
            $fecha = $_POST['fecha'] ?? '';
            $hora = $_POST['hora'] ?? '';
            $tipo_servicio = $_POST['tipo_servicio'] ?? '';

            // Validaciones básicas
            $permitidos = ['vacunacion','Control','castracion','baño'];
            if (!$fecha || !$hora || !in_array($tipo_servicio, $permitidos, true)) {
                header("Location: ver_turnos.php");
                exit();
            }

            // Evitar doble reserva: si existe otro turno pendiente mismo dia/hora/servicio
            $chk = $conn->prepare("SELECT COUNT(*) FROM turnos WHERE id <> :id AND fecha = :fecha AND hora = :hora AND tipo_servicio = :serv AND estado = 'Pendiente'");
            $chk->execute([':id'=>$id, ':fecha'=>$fecha, ':hora'=>$hora, ':serv'=>$tipo_servicio]);
            if ((int)$chk->fetchColumn() > 0) {
                header("Location: ver_turnos.php");
                exit();
            }

            $upd = $conn->prepare("UPDATE turnos SET fecha = :fecha, hora = :hora, tipo_servicio = :serv WHERE id = :id AND user_id = :uid");
            $upd->execute([':fecha' => $fecha, ':hora' => $hora, ':serv' => $tipo_servicio, ':id' => $id, ':uid' => $user_id]);
            header("Location: ver_turnos.php");
            exit();
        }
    }

    // Consulta para obtener los turnos incluyendo el estado
    $query = "SELECT t.id, t.fecha, t.hora, t.tipo_servicio, m.nombre AS mascota_nombre, t.estado 
              FROM turnos t 
              JOIN mascotas m ON t.mascota_id = m.id 
              WHERE t.user_id = :user_id 
              ORDER BY t.fecha, t.hora";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Turnos</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            overflow: hidden;
            background: linear-gradient(135deg, #f4f4f9 0%, #e8eef2 100%);
            color: #2c3e50;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .dark-mode {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #e0e0e0;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #025162 0%, #034854 100%);
            backdrop-filter: blur(10px);
            color: #ecf0f1;
            padding-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .user-name,
        .sidebar.collapsed span {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.2s ease;
        }

        .sidebar .toggle-menu {
            position: absolute;
            top: 25px;
            right: -18px;
            cursor: pointer;
            background: linear-gradient(135deg, #027a8d 0%, #025162 100%);
            padding: 12px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(2, 122, 141, 0.3);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
            z-index: 1001;
        }

        .sidebar .toggle-menu:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(2, 122, 141, 0.4);
        }

        .sidebar.collapsed .toggle-menu {
            right: -18px;
            transform: rotate(180deg);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            width: 100%;
            padding: 0 20px;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar.collapsed .profile-image {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: #ecf0f1;
            text-decoration: none;
            padding: 16px 24px;
            width: calc(100% - 30px);
            margin: 0 15px 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .sidebar a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .sidebar a:hover::before {
            left: 100%;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 16px;
            width: 50px;
            margin: 0 15px 12px 15px;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar.collapsed a:hover {
            transform: translateX(0) scale(1.05);
        }

        .sidebar i {
            margin-right: 12px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed i {
            margin-right: 0;
            font-size: 20px;
        }

        .sidebar .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding-bottom: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content {
            flex-grow: 1;
            padding: 40px 30px;
            margin-left: 280px; /* Mismo ancho que el sidebar */
            width: calc(100% - 280px);
            text-align: center;
            min-height: 100vh;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            overflow-x: hidden;
        }

        .sidebar.collapsed ~ .content {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        h1 {
            margin: 0 0 12px;
        }

        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #027a8d;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-pending {
            color: red;
            font-weight: bold;
        }
        .status-completed {
            color: green;
            font-weight: bold;
        }
        .status-lost {
            color: gray;
            font-weight: bold;
        }
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .edit-btn {
            background-color: #28a745;
        }
        .edit-btn:hover {
            background-color: #218838;
        }
        .cancel-btn {
            background-color: #dc3545;
        }
        .cancel-btn:hover {
            background-color: #c82333;
        }
        /* Desactivar colapso del sidebar en esta página */
        .sidebar .toggle-menu { display: none !important; }
        .sidebar.collapsed { width: 275px !important; }
        .sidebar.collapsed .user-name,
        .sidebar.collapsed span { display: inline !important; }

        /* Calendario - estilos básicos */
        .cal-container { max-width: 1100px; margin: 16px auto 90px; background: #fff; border-radius: 18px; box-shadow: 0 20px 50px rgba(2,122,141,0.15); overflow: hidden; border:1px solid #e6f0f2; }
        .cal-header { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; color:#fff; background: linear-gradient(135deg, #027a8d, #035c6b); }
        .cal-header h2 { margin:0; font-size: 22px; font-weight:800; letter-spacing: .3px; }
        .cal-nav { display:flex; gap:8px; align-items:center; }
        .cal-btn { background: rgba(255,255,255,0.14); color:#fff; border:none; padding:8px 12px; border-radius:10px; cursor:pointer; transition:.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,.08) inset; }
        .cal-btn:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
        .cal-week { display:grid; grid-template-columns: repeat(7, 1fr); background:linear-gradient(180deg,#f8fbfc,#f3f7fa); border-bottom:1px solid #e5e7eb; }
        .cal-week div { padding:6px; text-align:center; font-weight:700; color:#456; border-right:1px solid #e5e7eb; text-transform:uppercase; font-size:12px; letter-spacing:.5px; }
        .cal-week div:last-child { border-right: none; }
        .cal-grid { display:grid; grid-template-columns: repeat(7, 1fr); }
        .cal-cell { min-height:100px; border-right:1px solid #edf2f7; border-bottom:1px solid #edf2f7; padding:8px; background:#fff; transition:.2s ease; }
        .cal-cell:hover { background:#f8feff; box-shadow: inset 0 0 0 1px #cdeff5; }
        .cal-cell:last-child { border-right:none; }
        .cal-day-badge { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:10px; font-weight:700; color:#334155; background:#eef7f9; border:1px solid #dbeef2; }
        .cal-day-badge.today { background:#027a8d; color:#fff; border-color:#027a8d; box-shadow:0 6px 14px rgba(2,122,141,0.35); }
        .cal-turnos { margin-top:8px; display:flex; flex-direction:column; gap:8px; max-height:88px; overflow-y:auto; }
        .cal-turno { font-size:12px; background:#e6f7ff; color:#055b7d; padding:7px 9px; border-radius:10px; cursor:pointer; border:1px solid #cbefff; transition:.15s ease; }
        .cal-turno:hover { background:#dff3ff; transform: translateY(-1px); }
        .cal-more { font-size:12px; color:#6b7280; text-align:center; }
        .cal-add-btn { margin-left:auto; background:#e6fffa; color:#047857; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; }
        .cal-top { display:flex; align-items:center; gap:6px; justify-content:space-between; }

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:2000; padding:16px; }
        .modal { background:#fff; width:100%; max-width:520px; border-radius:18px; max-height:90vh; overflow-y:auto; overflow-x:hidden; box-shadow:0 30px 70px rgba(2,122,141,0.25); border:1px solid #e6f0f2; }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #e5e7eb; background:linear-gradient(135deg,#f8fdff,#f3f9fb); }
        .modal-body { padding:20px 22px; display:flex; flex-direction:column; gap:14px; }
        .modal-actions { display:flex; gap:10px; padding:16px 20px; border-top:1px solid #e5e7eb; }
        .btn { padding:10px 14px; border-radius:10px; border:none; cursor:pointer; font-weight:700; letter-spacing:.3px; }
        .btn-secondary { background:#eef2f7; color:#0f172a; border:1px solid #e2e8f0; }
        .btn-danger { background:#ef4444; color:#fff; box-shadow:0 6px 16px rgba(239,68,68,0.25); }
        .btn-primary { background:#027a8d; color:#fff; box-shadow:0 6px 16px rgba(2,122,141,0.25); }
        .btn-primary:hover { filter:brightness(1.05); transform: translateY(-1px); }
        .btn-danger:hover { filter:brightness(1.05); transform: translateY(-1px); }
        .btn-secondary:hover { background:#e6ebf5; }
        /* Inputs del modal sin causar scroll horizontal */
        .modal *, .modal *::before, .modal *::after { box-sizing: border-box; }
        .modal-label { display:block; font-weight:800; color:#0f172a; margin-bottom:8px; text-align:center; }
        .modal-input, .modal-select { width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; outline:none; background:#fff; color:#0f172a; }
        .modal-input:focus, .modal-select:focus { border-color:#027a8d; box-shadow:0 0 0 4px rgba(2,122,141,0.12); }
        .modal-note { font-size:14px; color:#374151; }
    </style>
    <style>
        /* Override para unificar estilos del sidebar con el dashboard de doctor */
        .sidebar { width: 275px; background-color: #025162; padding-top: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .sidebar a { background-color: #027a8d; padding: 15px 20px; width: calc(100% - 40px); margin-bottom: 15px; border-radius: 12px; font-size: 16px; }
        .sidebar a:hover { background-color: #03485f; transform: translateY(-1px); }
        .sidebar a.active { background-color: #ff6b35; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3); border: 2px solid rgba(255, 255, 255, 0.2); }
        .sidebar i { margin-right: 10px; font-size: 18px; }
        .content { margin-left: 275px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="profile-section">
        <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
    </div>
    <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
    <a href="sacar_turno.php"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
    <a href="ver_turnos.php" class="active"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
    <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
    <a href="registrar_mascota.php"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
    <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>

    <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span> Cerrar Sesión</span></a>
    </div>
</div>

<div class="content">
    <h1>Mis Turnos</h1>

    <!-- Calendario de Turnos (solo visualización) -->
    <div class="cal-container" id="calendario">
        <div class="cal-header">
            <div class="cal-nav">
                <button class="cal-btn" id="btn-prev" title="Mes anterior">◀</button>
                <button class="cal-btn" id="btn-today" title="Ir a hoy">Hoy</button>
            </div>
            <h2 id="cal-titulo">Mes Año</h2>
            <div class="cal-nav">
                <button class="cal-btn" id="btn-next" title="Mes siguiente">▶</button>
            </div>
        </div>
        <div class="cal-week">
            <div>Dom</div>
            <div>Lun</div>
            <div>Mar</div>
            <div>Mié</div>
            <div>Jue</div>
            <div>Vie</div>
            <div>Sáb</div>
        </div>
        <div class="cal-grid" id="cal-grid"></div>
    </div>

    <!-- Modal: editar / eliminar turno -->
    <div class="modal-overlay" id="modal-overlay">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <form method="POST" id="form-turno">
                <input type="hidden" name="accion" id="form-accion">
                <input type="hidden" name="id" id="form-id">
                <div class="modal-header">
                    <h3 id="modal-title">Editar turno</h3>
                    <button type="button" class="btn btn-secondary" id="modal-close">Cerrar</button>
                </div>
                <div class="modal-body">
                    <div>
                        <label><strong>Fecha</strong></label>
                        <input type="date" name="fecha" id="form-fecha" class="btn-secondary" style="width:100%;padding:10px;border-radius:10px;border:1px solid #d1d5db;">
                    </div>
                    <div>
                        <label><strong>Hora</strong></label>
                        <select name="hora" id="form-hora" class="btn-secondary" style="width:100%;padding:10px;border-radius:10px;border:1px solid #d1d5db;"></select>
                        <div style="font-size:12px;color:#6b7280;margin-top:6px;">Las opciones se ajustan según el servicio y la disponibilidad.</div>
                    </div>
                    <div>
                        <label><strong>Servicio</strong></label>
                        <select name="tipo_servicio" id="form-servicio" style="width:100%;padding:10px;border-radius:10px;border:1px solid #d1d5db;">
                            <option value="vacunacion">Vacunación</option>
                            <option value="Control">Control</option>
                            <option value="castracion">Castración</option>
                            <option value="baño">Baño</option>
                        </select>
                    </div>
                    <div id="form-detalles" style="font-size:14px;color:#374151"></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" id="btn-eliminar">Eliminar</button>
                    <button type="button" class="btn btn-secondary" id="modal-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    
</div>

<script>
    // Datos desde PHP -> JS
    const phpTurnos = <?php echo json_encode($turnos, JSON_UNESCAPED_UNICODE); ?>;
    // Transformar a { 'YYYY-MM-DD': [{hora, servicio, mascota, estado}] }
    const turnosByDate = {};
    (phpTurnos || []).forEach(t => {
        const key = t.fecha; // ya viene YYYY-MM-DD
        if (!turnosByDate[key]) turnosByDate[key] = [];
        turnosByDate[key].push({
            id: t.id,
            hora: t.hora,
            servicio: t.tipo_servicio,
            mascota: t.mascota_nombre,
            estado: t.estado,
        });
    });

    const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const calTitulo = document.getElementById('cal-titulo');
    const calGrid = document.getElementById('cal-grid');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    const modalOk = document.getElementById('modal-ok');
    const modalBody = document.getElementById('modal-body');
    const formHora = document.getElementById('form-hora');
    const formFecha = document.getElementById('form-fecha');
    const formServicio = document.getElementById('form-servicio');

    let fechaActual = new Date();

    function pad(n){ return String(n).padStart(2,'0'); }
    function keyFecha(date){ return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}`; }
    function keyYMD(y,m,d){ return `${y}-${pad(m)}-${pad(d)}`; }

    // Configuración de intervalos por servicio (igual que en sacar_turno.php)
    const serviceTimeSlots = {
        'vacunacion': 20,
        'Control': 20,
        'castracion': 60,
        'baño': 60
    };

    function buildSlots(interval){
        const slots = [];
        const startHour = 8, endHour = 18;
        for (let h = startHour; h < endHour; h++){
            for (let m = 0; m < 60; m += interval){
                if (h === endHour - 1 && m + interval > 60) break;
                slots.push(`${pad(h)}:${pad(m)}`);
            }
        }
        return slots;
    }

    async function refreshHoraOptions(currentId){
        const service = formServicio.value;
        const fecha = formFecha.value;
        formHora.innerHTML = '';
        if (!service || !fecha){ return; }

        const interval = serviceTimeSlots[service] || 30;
        const allSlots = buildSlots(interval);
        try {
            const url = `api_horas_ocupadas.php?fecha=${encodeURIComponent(fecha)}&tipo_servicio=${encodeURIComponent(service)}&exclude_id=${encodeURIComponent(currentId||0)}`;
            const res = await fetch(url);
            const data = await res.json();
            const ocupadas = (data && data.ok) ? (data.ocupadas||[]) : [];
            const disponibles = allSlots.filter(t => !ocupadas.includes(t));
            for (const t of disponibles){
                const opt = document.createElement('option');
                opt.value = t; opt.textContent = t; formHora.appendChild(opt);
            }
        } catch(e){
            // fallback: si falla la API, mostrar todos los slots
            for (const t of allSlots){
                const opt = document.createElement('option');
                opt.value = t; opt.textContent = t; formHora.appendChild(opt);
            }
        }
    }

    function renderCalendar(){
        const y = fechaActual.getFullYear();
        const m = fechaActual.getMonth();
        calTitulo.textContent = `${meses[m]} ${y}`;
        calGrid.innerHTML = '';
        const first = new Date(y, m, 1);
        const last = new Date(y, m+1, 0);
        const empty = first.getDay(); // 0=Dom
        // Espacios vacíos al inicio
        for(let i=0;i<empty;i++){
            const cell = document.createElement('div');
            cell.className = 'cal-cell';
            calGrid.appendChild(cell);
        }
        for(let d=1; d<=last.getDate(); d++){
            const cell = document.createElement('div');
            cell.className = 'cal-cell';
            const top = document.createElement('div');
            top.className = 'cal-top';
            const badge = document.createElement('span');
            const today = new Date();
            const isToday = today.getFullYear()===y && today.getMonth()===m && today.getDate()===d;
            badge.className = 'cal-day-badge' + (isToday ? ' today' : '');
            badge.textContent = d;
            top.appendChild(badge);
            cell.appendChild(top);

            const list = document.createElement('div');
            list.className = 'cal-turnos';
            const k = keyYMD(y, m+1, d);
            const items = (turnosByDate[k] || []).sort((a,b)=>a.hora.localeCompare(b.hora));
            items.slice(0,2).forEach(t => {
                const pill = document.createElement('div');
                pill.className = 'cal-turno';
                pill.textContent = `${t.hora} · ${t.mascota} · ${t.servicio}`;
                pill.addEventListener('click', ()=> openModal(k, t));
                list.appendChild(pill);
            });
            if(items.length>2){
                const more = document.createElement('div');
                more.className='cal-more';
                more.textContent = `+${items.length-2} más`;
                list.appendChild(more);
            }
            cell.appendChild(list);
            calGrid.appendChild(cell);
        }
    }

    function openModal(fechaKey, turno){
        // Prefill form
        document.getElementById('form-id').value = turno.id;
        document.getElementById('form-fecha').value = fechaKey;
        document.getElementById('form-servicio').value = turno.servicio;
        document.getElementById('form-accion').value = 'editar';
        document.getElementById('form-detalles').innerHTML = `<div><strong>Mascota:</strong> ${turno.mascota || ''}</div><div><strong>Estado:</strong> ${turno.estado || ''}</div>`;
        // Cargar horas disponibles según servicio/fecha, excluyendo la propia reserva
        refreshHoraOptions(turno.id).then(()=>{
            // Seleccionar la hora original si sigue disponible; si no, dejar primera
            const exists = Array.from(formHora.options).some(o=>o.value===turno.hora);
            if (exists){ formHora.value = turno.hora; }
        });
        modalOverlay.style.display = 'flex';
    }

    function closeModal(){ modalOverlay.style.display = 'none'; }

    btnPrev.addEventListener('click', ()=>{ const d=new Date(fechaActual); d.setMonth(d.getMonth()-1); fechaActual=d; renderCalendar(); });
    btnNext.addEventListener('click', ()=>{ const d=new Date(fechaActual); d.setMonth(d.getMonth()+1); fechaActual=d; renderCalendar(); });
    document.getElementById('btn-today').addEventListener('click', ()=>{ fechaActual = new Date(); renderCalendar(); });
    modalClose.addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e)=>{ if(e.target===modalOverlay) closeModal(); });

    // Reactualizar horas al cambiar servicio o fecha
    formServicio.addEventListener('change', ()=>{
        const id = document.getElementById('form-id').value;
        refreshHoraOptions(id);
    });
    formFecha.addEventListener('change', ()=>{
        const id = document.getElementById('form-id').value;
        refreshHoraOptions(id);
    });

    document.addEventListener('DOMContentLoaded', renderCalendar);

    // Submit handlers
    const form = document.getElementById('form-turno');
    document.getElementById('btn-guardar').addEventListener('click', ()=>{
        document.getElementById('form-accion').value = 'editar';
        form.submit();
    });
    document.getElementById('btn-eliminar').addEventListener('click', ()=>{
        if (confirm('¿Seguro que deseas eliminar este turno?')) {
            document.getElementById('form-accion').value = 'eliminar';
            form.submit();
        }
    });
</script>

<script src="alertas_clientes.js"></script>

</body>
</html>
