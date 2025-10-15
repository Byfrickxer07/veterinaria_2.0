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

    // Corregir turnos con fechas futuras que estén marcados como 'Terminado' (estado inválido)
    $corregir = $conn->prepare("UPDATE turnos 
                                SET estado = 'Pendiente'
                                WHERE user_id = :uid
                                  AND estado = 'Terminado'
                                  AND (fecha > CURDATE() OR (fecha = CURDATE() AND hora > CURTIME()))");
    $corregir->execute([':uid' => $user_id]);

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

            // Validar que no se pueda editar a una fecha pasada
            $fechaActual = date('Y-m-d');
            if ($fecha < $fechaActual) {
                header("Location: ver_turnos.php?error=fecha_pasada");
                exit();
            }

            // Validar que no se pueda editar a una fecha/hora pasada (mismo día)
            if ($fecha === $fechaActual) {
                $horaActual = date('H:i');
                if ($hora < $horaActual) {
                    header("Location: ver_turnos.php?error=hora_pasada");
                    exit();
                }
            }

            // Evitar doble reserva: si existe otro turno pendiente mismo dia/hora/servicio
            $chk = $conn->prepare("SELECT COUNT(*) FROM turnos WHERE id <> :id AND fecha = :fecha AND hora = :hora AND tipo_servicio = :serv AND estado = 'Pendiente'");
            $chk->execute([':id'=>$id, ':fecha'=>$fecha, ':hora'=>$hora, ':serv'=>$tipo_servicio]);
            if ((int)$chk->fetchColumn() > 0) {
                header("Location: ver_turnos.php?error=horario_ocupado");
                exit();
            }

            $upd = $conn->prepare("UPDATE turnos SET fecha = :fecha, hora = :hora, tipo_servicio = :serv WHERE id = :id AND user_id = :uid");
            $upd->execute([':fecha' => $fecha, ':hora' => $hora, ':serv' => $tipo_servicio, ':id' => $id, ':uid' => $user_id]);
            header("Location: ver_turnos.php?success=editado");
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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            overflow: hidden;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
            background-color: #f4f4f9;
        }

        .dark-mode {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #e0e0e0;
        }

        .sidebar {
            width: 275px;
            background-color: #025162;
            color: #ecf0f1;
            padding-top: 40px; 
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            transition: width 0.3s, background-color 0.3s, box-shadow 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 20px; 
            transition: margin-bottom 0.3s;
        }

        .profile-image {
            width: 120px; 
            height: 120px; 
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            transition: width 0.3s, height 0.3s;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: #ecf0f1;
            text-decoration: none;
            padding: 15px 20px;
            width: calc(100% - 40px);
            margin-bottom: 15px; 
            background-color: #027a8d;
            border-radius: 12px;
            font-size: 16px;
            transition: background-color 0.3s, padding 0.3s, transform 0.15s ease-in-out;
            box-sizing: border-box;
            position: relative;
        }

        .sidebar a:hover {
            background-color: #03485f;
            transform: translateY(-1px);
        }

        .sidebar a.active {
            background-color: #ff6b35;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar a.active:hover {
            background-color: #e55a2b;
        }

        .sidebar i {
            margin-right: 10px;
            font-size: 18px;
        }

        .sidebar span {
            transition: opacity 0.3s ease;
        }

        .sidebar .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding-bottom: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logout-button {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            margin-left: 275px;
            width: calc(100% - 275px);
            padding: 40px 30px;
            text-align: center;
            min-height: 100vh;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            overflow-x: hidden;
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
            color: #dc2626;
            font-weight: bold;
        }
        .status-completed {
            color: #16a34a;
            font-weight: bold;
        }
        .status-terminado {
            color: #dc2626;
            font-weight: bold;
            background-color: #fee2e2;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #fecaca;
        }
        .status-lost {
            color: #6b7280;
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
        .cal-turno.terminado { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; cursor:not-allowed; opacity:0.7; }
        .cal-turno.terminado:hover { background:#fee2e2; transform:none; }
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

        /* Estilos responsivos */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content {
                margin-left: 0;
                width: 100%;
            }
            
            .mobile-menu-toggle {
                display: block !important;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1100;
                background: #025162;
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                font-size: 1.5rem;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            }
        }
    </style>
</head>
<body>

<!-- Botón de menú móvil -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
    <i class='bx bx-menu'></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="profile-section">
        <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
    </div>
  
    <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
    <a href="sacar_turno.php"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
    <a href="ver_turnos.php" class="active"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
    <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
    <a href="registrar_mascota.php"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
    <a href="historial_cliente.php"><i class='bx bx-notepad'></i><span>Historial Clínico</span></a>
    <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>

    <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
    </div>
</div>

<div class="content">
    <h1>Mis Turnos</h1>
    
    <?php
   
    
    if (isset($_GET['error'])) {
        $mensaje = '';
        switch ($_GET['error']) {
            case 'fecha_pasada':
                $mensaje = 'No se pueden editar turnos a fechas pasadas.';
                break;
            case 'hora_pasada':
                $mensaje = 'No se pueden editar turnos a horas pasadas para el día actual.';
                break;
            case 'horario_ocupado':
                $mensaje = 'El horario seleccionado ya está ocupado. Por favor elige otro horario.';
                break;
            default:
                $mensaje = 'Ha ocurrido un error al procesar la solicitud.';
        }
        
        echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; border: 1px solid #f5c6cb;">
                <strong>Error:</strong> ' . htmlspecialchars($mensaje) . '
              </div>';
    }
    ?>

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

    <!-- Lista de Turnos -->
    
        
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
                        <input type="date" name="fecha" id="form-fecha" class="btn-secondary" style="width:100%;padding:10px;border-radius:10px;border:1px solid #d1d5db;" min="<?php echo date('Y-m-d'); ?>">
                        <div style="font-size:12px;color:#6b7280;margin-top:6px;">No se pueden seleccionar fechas pasadas.</div>
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
                    <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar cambios</button>
                    <button type="button" class="btn btn-danger" id="btn-eliminar">Eliminar</button>
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
                
                // Verificar si la fecha es futura
                const fechaTurno = new Date(k);
                const fechaActual = new Date();
                fechaActual.setHours(0, 0, 0, 0);
                const esFechaFutura = fechaTurno > fechaActual;
                
                // Aplicar clase 'terminado' solo si el estado es 'Terminado' Y la fecha no es futura
                if (t.estado === 'Terminado' && !esFechaFutura) {
                    pill.classList.add('terminado');
                }
                
                pill.textContent = `${t.hora} · ${t.mascota} · ${t.servicio}`;
                
                // Solo permitir edición si el turno no está terminado O si es fecha futura
                if (t.estado !== 'Terminado' || esFechaFutura) {
                    pill.addEventListener('click', ()=> openModal(k, t));
                } else {
                    pill.title = 'Este turno ya está terminado y no se puede editar';
                }
                
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
        // Verificar si la fecha es futura
        const fechaTurno = new Date(fechaKey);
        const fechaActual = new Date();
        fechaActual.setHours(0, 0, 0, 0);
        const esFechaFutura = fechaTurno > fechaActual;
        
        // Verificar si el turno está terminado Y no es fecha futura
        if (turno.estado === 'Terminado' && !esFechaFutura) {
            Swal.fire({
                title: 'Turno Terminado',
                text: 'Este turno ya está terminado y no se puede editar.',
                icon: 'info',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
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

    // Función para abrir modal desde la tabla
    function openModalFromTable(fechaKey, turno){
        openModal(fechaKey, turno);
    }

    function closeModal(){ modalOverlay.style.display = 'none'; }

    btnPrev.addEventListener('click', ()=>{ const d=new Date(fechaActual); d.setMonth(d.getMonth()-1); fechaActual=d; renderCalendar(); });
    btnNext.addEventListener('click', ()=>{ const d=new Date(fechaActual); d.setMonth(d.getMonth()+1); fechaActual=d; renderCalendar(); });
    document.getElementById('btn-today').addEventListener('click', ()=>{ fechaActual = new Date(); renderCalendar(); });
    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e)=>{ if(e.target===modalOverlay) closeModal(); });

    // Reactualizar horas al cambiar servicio o fecha
    formServicio.addEventListener('change', ()=>{
        const id = document.getElementById('form-id').value;
        refreshHoraOptions(id);
    });
    formFecha.addEventListener('change', ()=>{
        const id = document.getElementById('form-id').value;
        refreshHoraOptions(id);
        
        // Validar que no se pueda seleccionar una hora pasada si es el día actual
        const fechaSeleccionada = formFecha.value;
        const fechaActual = new Date().toISOString().split('T')[0];
        
        if (fechaSeleccionada === fechaActual) {
            const horaActual = new Date();
            const horaActualStr = horaActual.getHours().toString().padStart(2, '0') + ':' + 
                                 horaActual.getMinutes().toString().padStart(2, '0');
            
            // Filtrar opciones de hora para excluir horas pasadas
            const opciones = formHora.querySelectorAll('option');
            opciones.forEach(opcion => {
                if (opcion.value < horaActualStr) {
                    opcion.style.display = 'none';
                } else {
                    opcion.style.display = 'block';
                }
            });
        } else {
            // Mostrar todas las opciones si no es el día actual
            const opciones = formHora.querySelectorAll('option');
            opciones.forEach(opcion => {
                opcion.style.display = 'block';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', renderCalendar);

    // Submit handlers
    const form = document.getElementById('form-turno');
    document.getElementById('btn-guardar').addEventListener('click', (e)=>{
        e.preventDefault();
        
        // Validaciones del lado del cliente
        const fecha = formFecha.value;
        const hora = formHora.value;
        const fechaActual = new Date().toISOString().split('T')[0];
        
        // Validar fecha pasada
        if (fecha < fechaActual) {
            Swal.fire({
                title: 'Error',
                text: 'No se pueden seleccionar fechas pasadas.',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        // Validar hora pasada si es el día actual
        if (fecha === fechaActual) {
            const horaActual = new Date();
            const horaActualStr = horaActual.getHours().toString().padStart(2, '0') + ':' + 
                                 horaActual.getMinutes().toString().padStart(2, '0');
            
            if (hora < horaActualStr) {
                Swal.fire({
                    title: 'Error',
                    text: 'No se pueden seleccionar horas pasadas para el día actual.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
        }
        
        // Validar que se haya seleccionado una hora
        if (!hora) {
            Swal.fire({
                title: 'Error',
                text: 'Por favor selecciona una hora disponible.',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        document.getElementById('form-accion').value = 'editar';
        form.submit();
    });
    document.getElementById('btn-eliminar').addEventListener('click', ()=>{
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'No podrás revertir esta acción. El turno será eliminado permanentemente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-accion').value = 'eliminar';
                form.submit();
            }
        });
    });

    // Script para el menú móvil (igual a client_dashboard)
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');

    if (window.innerWidth <= 992) {
        mobileMenuToggle.style.display = 'block';
        
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic en un enlace
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                }
            });
        });
    }

    // Actualizar en caso de cambio de tamaño de ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            mobileMenuToggle.style.display = 'none';
            sidebar.classList.remove('active');
        } else {
            mobileMenuToggle.style.display = 'block';
        }
    });

    const logoutButton = document.getElementById('logout-button');
    logoutButton.addEventListener('click', function(event) {
        event.preventDefault();
        Swal.fire({
            title: '¿Estás seguro de que deseas cerrar sesión?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    });
</script>

<script src="alertas_clientes.js"></script>

</body>
</html>
