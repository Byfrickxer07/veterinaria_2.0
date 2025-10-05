<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "veterinaria");

if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

// Consulta para obtener todos los turnos
$sql = "SELECT t.id AS turno_id, t.fecha, t.hora, t.tipo_servicio, m.id AS mascota_id, m.nombre AS mascota, u.nombre_usuario AS cliente, t.estado
        FROM turnos t
        JOIN mascotas m ON t.mascota_id = m.id
        JOIN user u ON t.user_id = u.id";

$result = $mysqli->query($sql);

if ($result === FALSE) {
    die("Error en la consulta: " . $mysqli->error);
}

if ($result->num_rows === 0) {
    $no_turnos = true;
} else {
    $no_turnos = false;
}

// Procesar la solicitud para marcar un turno como terminado
if (isset($_POST['mark_completed'])) {
    $turno_id = $_POST['turno_id'];
    $update_sql = "UPDATE turnos SET estado = 'terminado' WHERE id = ?";
    $stmt = $mysqli->prepare($update_sql);
    $stmt->bind_param('i', $turno_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']); // Redirigir para evitar el envío del formulario múltiples veces
    exit;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Turnos</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<style>
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        overflow: auto;
        background: #f5f7fb; /* más neutro y limpio */
        color: #0f172a;
        transition: background 0.3s, color 0.3s;
        font-size: 15px;
    }

    .dark-mode {
        background-color: #1c1c1c;
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
        padding: 24px 20px 32px;
        text-align: left; /* alinear contenido a la izquierda */
        min-height: 100vh; /* evitar cortes por altura fija */
        transition: padding 0.3s;
    }
    h1 {
        margin: 4px 0 16px;
        font-weight: 700;
        color: #0f172a; /* sin gradiente para mejor legibilidad */
        letter-spacing: .2px;
    }

    /* Card que envuelve la tabla */
    .table-card {
        width: 95%;
        max-width: 1180px;
        margin: 0 auto 20px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(2,122,141,0.08); /* sombra más sutil */
        overflow: hidden;
        border: 1px solid #e9eef2;
        text-align: left;
    }

    .table-scroll { max-height: calc(100vh - 260px); overflow: auto; }
    .table-scroll::-webkit-scrollbar { height: 10px; width: 10px; }
    .table-scroll::-webkit-scrollbar-thumb { background:#cfe6ea; border-radius: 10px; }
    .table-scroll::-webkit-scrollbar-track { background: #f3f7f9; }

    .table-card table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-card thead th {
        position: sticky;
        top: 0;
        background: #025162; /* sólido y con mayor contraste */
        color: #fff;
        padding: 14px 16px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: .4px;
    }

    .table-card tbody td { padding: 12px 16px; border-bottom: 1px solid #eef2f7; color: #0f172a; vertical-align: middle; font-size: 14px; }
    .table-card tbody tr:nth-child(even) { background: #fafcff; }

    .table-card tbody tr { transition: background .15s ease, transform .08s ease; }
    .table-card tbody tr:hover { background: #f7fbfd; }

    .action-links a {
        color: #027a8d;
        text-decoration: none;
        margin-right: 10px;
        font-weight: 600;
    }
    .action-links a:hover { text-decoration: underline; }

    /* Estado (minimal) */
    .badge { display:inline-block; padding:4px 8px; border-radius:8px; font-size:12px; font-weight:700; }
    .badge-terminado { background:#e7f6ee; color:#116149; border:1px solid #cfe9dc; }
    .badge-pendiente { background:#f8f1e7; color:#8a3b12; border:1px solid #efd8bf; }

    /* Botón acción */
    .mark-completed {
        background: linear-gradient(135deg,#027a8d,#025162);
        color:#fff; border:none; padding:8px 14px; border-radius:10px; cursor:pointer; font-size:13px; font-weight:700;
        box-shadow:0 6px 16px rgba(2,122,141,0.18);
    }
    .mark-completed:hover { filter:brightness(1.05); transform: translateY(-1px); }

    .turnos{ margin-left: 0; }
    
    /* Toolbar de filtros/búsqueda */
    .table-toolbar { display:flex; gap:10px; align-items:center; padding:12px 16px; background:#ffffff; border-bottom:1px solid #eef2f7; position: sticky; top: 0; z-index: 2; }
    .toolbar-input, .toolbar-select { padding:10px 12px; border:1px solid #d6dee3; border-radius:10px; background:#fff; font-size:14px; transition: box-shadow .15s ease, border-color .15s ease; }
    .toolbar-input { flex:1; }
    .toolbar-button { background: #027a8d; color:#fff; border:none; padding:10px 14px; border-radius:10px; font-weight:700; cursor:pointer; box-shadow:0 2px 6px rgba(2,122,141,0.18); }
    .toolbar-button:hover { filter:brightness(1.03); }
    .toolbar-input:focus, .toolbar-select:focus { border-color:#027a8d; box-shadow:0 0 0 3px rgba(2,122,141,0.15); }

    /* Responsive */
    @media (max-width: 900px) {
        .table-card { width: 96%; }
        .table-card thead th, .table-card tbody td { padding: 12px 14px; }
        .toolbar-input { min-width: 160px; }
    }
       
    </style>
    <style>
        /* Desactivar colapso del sidebar en esta página */
        .sidebar .toggle-menu { display: none !important; }
        .sidebar.collapsed { width: 275px !important; }
        .sidebar.collapsed .user-name,
        .sidebar.collapsed span { display: inline !important; }
    </style>
</head>
<body>


<div class="sidebar">
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Profile Image" class="profile-image">
        </div>
        
        <a href="doctor_dashboard.php"><i class='bx bxs-dashboard'></i><span>Inicio</span></a>
       
        <a href="gestionar_usudoc.php"><i class='bx bxs-user'></i><span>Gestión de Usuarios</span></a>
        <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>

<div class="content">
    <h1>Lista de Turnos</h1>
    <div class="turnos">
    <?php if ($no_turnos): ?>
        <p>No hay turnos disponibles.</p>
    <?php else: ?>
        <div class="table-card">
            <div class="table-toolbar">
                <input id="searchInput" class="toolbar-input" type="text" placeholder="Buscar por cliente, mascota o servicio...">
                <select id="filterServicio" class="toolbar-select">
                    <option value="">Servicio: Todos</option>
                    <option value="vacunacion">Vacunación</option>
                    <option value="Control">Control</option>
                    <option value="castracion">Castración</option>
                    <option value="baño">Baño</option>
                </select>
                <select id="filterEstado" class="toolbar-select">
                    <option value="">Estado: Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="terminado">Terminado</option>
                </select>
                <button id="applyFilters" class="toolbar-button" type="button">Filtrar</button>
                <button id="clearFilters" class="toolbar-button" type="button" style="background:linear-gradient(135deg,#6b7280,#374151)">Limpiar</button>
            </div>
            <div id="noResults" style="display:none;padding:14px 16px;color:#991b1b;background:#fee2e2;border:1px solid #fecaca;margin:8px 16px;border-radius:10px;font-weight:600;">No se ha encontrado ningún turno con esos filtros.</div>
            <div class="table-scroll">
            <table id="turnosTable">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Servicio</th>
                        <th>Mascota</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Historial Médico</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-servicio="<?php echo htmlspecialchars(strtolower($row['tipo_servicio'])); ?>" data-estado="<?php echo htmlspecialchars(strtolower($row['estado'])); ?>">
                            <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                            <td><?php echo htmlspecialchars($row['hora']); ?></td>
                            <td class="col-servicio"><?php echo htmlspecialchars($row['tipo_servicio']); ?></td>
                            <td class="col-mascota"><?php echo htmlspecialchars($row['mascota']); ?></td>
                            <td class="col-cliente"><?php echo htmlspecialchars($row['cliente']); ?></td>
                            <td>
                                <?php $st = strtolower($row['estado']); ?>
                                <span class="badge <?php echo $st === 'terminado' ? 'badge-terminado' : 'badge-pendiente'; ?>"><?php echo htmlspecialchars($row['estado']); ?></span>
                            </td>
                            <td class="action-links">
                                <a href="historial_mascota.php?mascota_id=<?php echo htmlspecialchars($row['mascota_id']); ?>">Ver Historial</a>
                            </td>
                            <td>
                                <?php if (strtolower($row['estado']) !== 'terminado'): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="turno_id" value="<?php echo htmlspecialchars($row['turno_id']); ?>">
                                        <button type="submit" name="mark_completed" class="mark-completed">Marcar como Terminado</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>
    </div>
    </div> 
    
    

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="alertas_clientes.js"></script>
<script>
    const menuToggle = document.querySelector('.toggle-menu');
    const sidebar = document.querySelector('.sidebar');
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const body = document.body;

    menuToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        const icon = menuToggle.querySelector('i');
        icon?.classList.toggle('bx-chevron-left');
        icon?.classList.toggle('bx-chevron-right');
    });

    darkModeToggle?.addEventListener('change', () => {
        body.classList.toggle('dark-mode');
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

    // Filtro de tabla
    const table = document.getElementById('turnosTable');
    const searchInput = document.getElementById('searchInput');
    const filterServicio = document.getElementById('filterServicio');
    const filterEstado = document.getElementById('filterEstado');
    const noResults = document.getElementById('noResults');
    const applyBtn = document.getElementById('applyFilters');
    const clearBtn = document.getElementById('clearFilters');

    function normalize(text){
        return (text||'')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g,'');
    }

    function applyFilters(){
        const q = normalize(searchInput.value);
        const serv = normalize(filterServicio.value);
        const est = normalize(filterEstado.value);

        let visibleCount = 0;
        Array.from(table.tBodies[0].rows).forEach(tr => {
            const tdCliente = normalize(tr.querySelector('.col-cliente')?.textContent);
            const tdMascota = normalize(tr.querySelector('.col-mascota')?.textContent);
            const tdServicio = normalize(tr.querySelector('.col-servicio')?.textContent);
            const rowServ = normalize(tr.getAttribute('data-servicio'));
            const rowEst = normalize(tr.getAttribute('data-estado'));

            let matches = true;
            if (q) {
                matches = (tdCliente.includes(q) || tdMascota.includes(q) || tdServicio.includes(q));
            }
            if (matches && serv) {
                matches = rowServ === serv;
            }
            if (matches && est) {
                matches = rowEst === est;
            }
            tr.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    function clearFilters(){
        searchInput.value = '';
        filterServicio.value = '';
        filterEstado.value = '';
        applyFilters();
    }

    applyBtn?.addEventListener('click', applyFilters);
    clearBtn?.addEventListener('click', clearFilters);
</script>
</body>
</html>
