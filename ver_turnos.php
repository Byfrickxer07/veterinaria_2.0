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
    <style>
           body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            overflow: hidden;
            background-color: #f4f4f9;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
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
            transition: width 0.3s, background-color 0.3s;
            position: relative;
        }

        .sidebar.collapsed {
            width: 75px;
        }

        .sidebar.collapsed .user-name,
        .sidebar.collapsed span {
            display: none;
        }

        .sidebar.collapsed .profile-section {
            text-align: center;
        }

        .sidebar .toggle-menu {
            position: absolute;
            top: 30px; 
            right: -15px; 
            cursor: pointer;
            background-color: #027a8d; 
            padding: 10px;
            border-radius: 50%; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); 
            transition: right 0.3s ease, background-color 0.3s;
        }

        .sidebar.collapsed .toggle-menu {
            right: -15px; 
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

        .sidebar.collapsed .profile-image {
            width: 60px; 
            height: 60px; 
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
            transition: background-color 0.3s, padding 0.3s;
            box-sizing: border-box;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 15px;
        }

        .sidebar a:hover {
            background-color: #03485f;
        }

        .sidebar i {
            margin-right: 10px;
        }

        .sidebar.collapsed i {
            margin-right: 0;
        }

        .sidebar .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content {
            flex-grow: 1;
            padding: 50px 20px; 
            text-align: center;
            height: 100vh;
            transition: padding 0.3s;
        }

        h1 {
            margin-top: 0;
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
        .cal-container { max-width: 1100px; margin: 20px auto; background: #fff; border-radius: 18px; box-shadow: 0 20px 50px rgba(2,122,141,0.15); overflow: hidden; border:1px solid #e6f0f2; }
        .cal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; color:#fff; background: linear-gradient(135deg, #027a8d, #035c6b); }
        .cal-header h2 { margin:0; font-size: 22px; font-weight:800; letter-spacing: .3px; }
        .cal-nav { display:flex; gap:8px; align-items:center; }
        .cal-btn { background: rgba(255,255,255,0.14); color:#fff; border:none; padding:8px 12px; border-radius:10px; cursor:pointer; transition:.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,.08) inset; }
        .cal-btn:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
        .cal-week { display:grid; grid-template-columns: repeat(7, 1fr); background:linear-gradient(180deg,#f8fbfc,#f3f7fa); border-bottom:1px solid #e5e7eb; }
        .cal-week div { padding:12px; text-align:center; font-weight:700; color:#456; border-right:1px solid #e5e7eb; text-transform:uppercase; font-size:12px; letter-spacing:.5px; }
        .cal-week div:last-child { border-right: none; }
        .cal-grid { display:grid; grid-template-columns: repeat(7, 1fr); }
        .cal-cell { min-height:130px; border-right:1px solid #edf2f7; border-bottom:1px solid #edf2f7; padding:10px; background:#fff; transition:.2s ease; }
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
</head>
<body>

<div class="sidebar">
<div class="toggle-menu">
        <i class='bx bx-chevron-left' id="menu-toggle"></i> 
    </div>

    <div class="profile-section">
        <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
        
    </div>
    <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
    <a href="sacar_turno.php"><i class='bx bx-calendar'></i><span>Sacar turno</span></a>
    <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Gestionar Perfil</span></a>
    <a href="registrar_mascota.php"><i class='bx bx-plus'></i><span>Añadir tus mascotas</span></a>

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
                        <input type="time" name="hora" id="form-hora" class="btn-secondary" style="width:100%;padding:10px;border-radius:10px;border:1px solid #d1d5db;">
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

    let fechaActual = new Date();

    function pad(n){ return String(n).padStart(2,'0'); }
    function keyFecha(date){ return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}`; }
    function keyYMD(y,m,d){ return `${y}-${pad(m)}-${pad(d)}`; }

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
        document.getElementById('form-hora').value = turno.hora;
        document.getElementById('form-servicio').value = turno.servicio;
        document.getElementById('form-accion').value = 'editar';
        document.getElementById('form-detalles').innerHTML = `<div><strong>Mascota:</strong> ${turno.mascota || ''}</div><div><strong>Estado:</strong> ${turno.estado || ''}</div>`;
        modalOverlay.style.display = 'flex';
    }

    function closeModal(){ modalOverlay.style.display = 'none'; }

    btnPrev.addEventListener('click', ()=>{ const d=new Date(fechaActual); d.setMonth(d.getMonth()-1); fechaActual=d; renderCalendar(); });
    btnNext.addEventListener('click', ()=>{ const d=new Date(fechaActual); d.setMonth(d.getMonth()+1); fechaActual=d; renderCalendar(); });
    document.getElementById('btn-today').addEventListener('click', ()=>{ fechaActual = new Date(); renderCalendar(); });
    modalClose.addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e)=>{ if(e.target===modalOverlay) closeModal(); });

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

</body>
</html>
