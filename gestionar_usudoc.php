<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$alertMessage = '';
$selectedUserId = null;
$selectedPetId = null;
$historyMessage = '';

// Mensajes desde guardar_historial.php
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'ok') {
        $alertMessage = 'Historial clínico guardado exitosamente.';
    } elseif ($_GET['status'] === 'error') {
        $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Error desconocido';
        $alertMessage = 'Error al guardar el historial clínico: ' . htmlspecialchars($msg);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["view_pets"])) {
        $selectedUserId = $_POST["user_id"];
    }
    if (isset($_POST["add_history"])) {
        $selectedPetId = $_POST["pet_id"];
    }
}

// Obtener clientes
$sql = "SELECT id, nombre_usuario, correo_electronico FROM user WHERE rol = 'cliente'";
$result = $conn->query($sql);

// Obtener mascotas del usuario seleccionado
$pets = [];
if ($selectedUserId) {
    $sqlPets = "SELECT id, nombre, especie, raza, edad FROM mascotas WHERE user_id = $selectedUserId";
    $petsResult = $conn->query($sqlPets);
    if ($petsResult->num_rows > 0) {
        while ($row = $petsResult->fetch_assoc()) {
            $pets[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
         body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background: linear-gradient(135deg, #f4f7fb 0%, #eef4f7 100%);
            color: #0f172a;
            transition: background 0.3s, color 0.3s;
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
            transition: width 0.3s, background-color 0.3s, box-shadow 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .sidebar.collapsed {
            width: 275px;
        }

        .sidebar.collapsed .user-name,
        .sidebar.collapsed span {
            display: inline;
        }

        .sidebar .toggle-menu {
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
            margin-bottom: 12px;
            border: 3px solid #ffffff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            background-color: #fff;
            transition: width 0.3s, height 0.3s, box-shadow .2s ease;
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
            transition: background-color 0.3s, padding 0.3s, transform 0.15s ease-in-out;
            box-sizing: border-box;
            position: relative;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 15px;
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

        .profile-image {
            width: 120px; 
            height: 120px; 
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            transition: width 0.3s, height 0.3s;
        }

        .sidebar .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding-bottom: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Estilo específico para el botón de cierre de sesión */
        .logout-button {
            margin-bottom: 30px; /* Añadido margen inferior adicional */
        }

        .content {
            flex-grow: 1;
            margin-left: 275px; 
            padding: 32px 24px; 
            min-height: 100vh;
        }

        h1 {
            margin: 6px 0 18px;
            font-weight: 800;
            background: linear-gradient(135deg, #027a8d 0%, #025162 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: .2px;
            position: relative;
            text-align: center;
        }
        h1::after { content:''; position:absolute; left:50%; transform:translateX(-50%); bottom:-10px; width:70px; height:4px; border-radius:2px; background: linear-gradient(135deg, #027a8d 0%, #025162 100%); }

        /* Subtítulos centrados */
        h2 { text-align: center; margin: 20px 0 10px; position: relative; }
        h2::after { content:''; position:absolute; left:50%; transform:translateX(-50%); bottom:-8px; width:60px; height:3px; border-radius:2px; background: linear-gradient(135deg, #027a8d 0%, #025162 100%); }

        .toggle-switch-container {
            background-color: transparent; 
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            padding: 0;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 5px;
        }

        .toggle-switch i {
            color: #fff;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-switch label {
            background-color: #ccc;
            border-radius: 15px;
            cursor: pointer;
            display: inline-block;
            height: 24px;
            position: relative;
            width: 50px;
        }

        .toggle-switch label::after {
            background-color: #fff;
            border-radius: 50%;
            content: '';
            height: 20px;
            left: 2px;
            position: absolute;
            top: 2px;
            transition: all 0.3s;
            width: 20px;
        }

        .toggle-switch input:checked + label {
            background-color: #66bb6a;
        }

        .toggle-switch input:checked + label::after {
            transform: translateX(26px);
        }

        .toggle-switch span {
            color: #fff;
            font-size: 14px;
        }

        .dark-mode .sidebar {
            background-color: #2e2e2e;
        }

        .dark-mode .sidebar a {
            background-color: #3d3d3d;
            color: #e0e0e0;
        }

        .dark-mode .sidebar a:hover {
            background-color: #5c5c5c;
        }

        .alert {
            background-color: #ffdddd;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .table-card { width: 92%; max-width: 1100px; margin: 0 auto 24px; background: #fff; border-radius: 18px; box-shadow: 0 24px 60px rgba(2,122,141,0.14); overflow: hidden; border: 1px solid #e6f0f2; text-align:left; }
        .table-card table { width:100%; border-collapse: separate; border-spacing:0; }
        .table-card thead th { position:sticky; top:0; background: #0b5b66; /* teal sólido similar a la foto */ color:#fff; padding:14px 18px; font-weight:700; text-transform:uppercase; font-size:12px; letter-spacing:.4px; border-right:1px solid rgba(255,255,255,0.18); text-align:center; }
        .table-card thead th:last-child { border-right:none; }
        .table-card tbody td { padding:14px 18px; border-bottom:1px solid #e8eef2; border-right:1px solid #e8eef2; color:#0f172a; text-align:center; vertical-align: middle; background:#ffffff; }
        .table-card tbody td:last-child { border-right:none; }
        .table-card tbody tr:nth-child(even){ background:#fbfdff; }
        .table-card tbody tr:hover{ background:#f7fbfd; }
        .table-card table tr:first-child th:first-child { border-top-left-radius: 0; }
        .table-card table tr:first-child th:last-child { border-top-right-radius: 0; }

        .button { background:#0b5b66; color:#fff; border:none; padding:10px 18px; text-align:center; display:inline-inline; font-size:14px; margin:4px 4px; cursor:pointer; border-radius:9999px; box-shadow:0 6px 14px rgba(11,91,102,0.28); transition: filter .15s ease, transform .1s ease; }
        .button:hover { filter:brightness(1.04); transform: translateY(-1px); }

        /* Enlaces de acción estilo link (como Ver Historial de la foto) */
        .link-action { color:#0b5b66; font-weight:700; text-decoration:none; }
        .link-action:hover { text-decoration:underline; }

        /* Toolbar de filtros/búsqueda (clientes) */
        .table-toolbar { display:flex; gap:10px; align-items:center; padding:12px 16px; background:#ffffff; border:1px solid #e6f0f2; border-radius:14px; margin: 10px auto 16px; width:92%; max-width:1100px; }
        .toolbar-input { flex:1; padding:10px 12px; border:1px solid #ccd9de; border-radius:10px; background:#fff; font-size:14px; transition: box-shadow .15s ease, border-color .15s ease; }
        .toolbar-button { background: #027a8d; color:#fff; border:none; padding:10px 14px; border-radius:10px; font-weight:700; cursor:pointer; box-shadow:0 2px 6px rgba(2,122,141,0.18); }
        .toolbar-button:hover { filter:brightness(1.03); }
        .toolbar-input:focus { border-color:#027a8d; box-shadow:0 0 0 3px rgba(2,122,141,0.15); }

        /* Centrado específico para las tablas de clientes y mascotas */
        #clientesTable th, #clientesTable td,
        #mascotasTable th, #mascotasTable td { text-align: center !important; vertical-align: middle; }
        #clientesTable td form { display: inline-block; }

        .modal { display:none; position:fixed; z-index:1000; inset:0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); padding:24px 12px; overflow:auto; }

        .modal-content { background:#fff; margin: 40px auto; padding:0; width:92%; max-width:620px; max-height:95vh; border-radius:18px; overflow:hidden; box-shadow: 0 30px 70px rgba(2,122,141,0.22); position:relative; animation: modalIn .25s ease-out; display:flex; flex-direction:column; }
        .modal-header { padding:16px 20px; background: linear-gradient(135deg,#027a8d,#035c6b); color:#fff; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:2; }
        .modal-body { padding:20px; overflow:auto; flex: 1 1 auto; }
        .modal-footer { position:sticky; bottom:0; background:#f7fafb; padding:12px 18px; border-top:1px solid #e6f0f2; display:flex; justify-content:flex-end; gap:8px; }
        @keyframes modalIn { from { opacity:0; transform: translateY(20px);} to { opacity:1; transform: translateY(0);} }

        .modal-content h2 { color:#fff; margin:0; font-weight:800; }

        .close { color:#fff; font-size:26px; font-weight:800; line-height:1; cursor:pointer; padding:4px 8px; border-radius:8px; transition: background .2s ease; }
        .close:hover, .close:focus { background: rgba(255,255,255,0.15); }

        /* Tabla de formulario dentro del modal */
        .form-table { width:100%; border-collapse: separate; border-spacing:0; background:#fff; border:1px solid #e6f0f2; border-radius:12px; overflow:hidden; }
        .form-table tr:nth-child(even){ background:#fafcff; }
        .form-table th, .form-table td { padding:12px 14px; text-align:left; vertical-align:top; border-bottom:1px solid #eef2f7; }
        .form-table th { width:38%; background:#f7fbfc; font-weight:700; color:#0f172a; }
        .form-table tr:last-child th, .form-table tr:last-child td { border-bottom:none; }
        .form-table input[type="text"],
        .form-table input[type="date"],
        .form-table textarea { width:100%; padding:10px 12px; border:1px solid #ccd4dd; border-radius:10px; outline:none; transition:border-color .15s ease, box-shadow .15s ease; box-sizing:border-box; }
        .form-table textarea { min-height:80px; resize:vertical; }
        .form-table input:focus, .form-table textarea:focus { border-color:#027a8d; box-shadow:0 0 0 3px rgba(2,122,141,.15); }

        @media (max-width: 600px){
            .form-table th { width:45%; }
            .form-table th, .form-table td { display:block; width:100%; }
            .form-table th { border-bottom:none; }
        }
        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            height: 80px;
            resize: vertical; /* Permite redimensionar verticalmente */
        }

    </style>
</head>
<body>
<div class="sidebar">
    <div class="profile-section">
        <img src="logo_perro.jpg" alt="Profile Image" class="profile-image">
    </div>
    <a href="doctor_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
    <a href="gestionar_usudoc.php" class="active"><i class='bx bx-user'></i><span>Gestionar Usuarios</span></a>
    <a href="gestionar_turnosdoc.php"><i class='bx bx-calendar'></i><span>Gestionar Turnos</span></a>

    <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
    </div>
</div>

    <div class="content">
        <?php if ($alertMessage): ?>
            <div class="alert <?= strpos($alertMessage, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <?= $alertMessage ?>
            </div>
        <?php endif; ?>

        <h1>Gestión de Clientes</h1>

        <div class="table-toolbar">
            <input id="searchCliente" class="toolbar-input" type="text" placeholder="Buscar por nombre o correo...">
            <button id="applyFilterClientes" class="toolbar-button" type="button">Filtrar</button>
            <button id="clearFilterClientes" class="toolbar-button" type="button" style="background:#6b7280">Limpiar</button>
            <span id="clientesCounter" style="margin-left:auto; font-weight:700; color:#025162;"></span>
        </div>

        <div class="table-card">
        <table id="clientesTable">
            <tr>
              
                <th>Nombre de Usuario</th>
                <th>Correo Electrónico</th>
                <th>Acciones</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                       
                        <td><?= $row["nombre_usuario"] ?></td>
                        <td><?= $row["correo_electronico"] ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <input type="submit" name="view_pets" value="Ver Mascotas" class="button">
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No se encontraron clientes.</td>
                </tr>
            <?php endif; ?>
        </table>
        </div>

        <div id="noClientes" style="display:none; width:92%; max-width:1100px; margin:8px auto 0; padding:12px 14px; background:#fee2e2; border:1px solid #fecaca; border-radius:10px; color:#7f1d1d; font-weight:600;">No se encontraron clientes con ese criterio.</div>

        <?php if ($selectedUserId && !empty($pets)): ?>
            <h2 style=" margin-bottom: 20px;">Mascotas Registradas</h2>
            <div class="table-card">
            <div class="table-toolbar" style="margin-top:16px;">
                <input id="searchMascota" class="toolbar-input" type="text" placeholder="Buscar mascota por nombre/raza...">
                <button id="applyFilterMascotas" class="toolbar-button" type="button">Filtrar</button>
                <button id="clearFilterMascotas" class="toolbar-button" type="button" style="background:#6b7280">Limpiar</button>
                <span id="mascotasCounter" style="margin-left:auto; font-weight:700; color:#025162;"></span>
            </div>
            <table id="mascotasTable">
                <tr>
                    <th>Nombre</th>
                    <th>Especie</th>
                    <th>Raza</th>
                    <th>Edad</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($pets as $pet): ?>
                    <tr>
                        <td><?= $pet["nombre"] ?></td>
                        <td><?= $pet["especie"] ?></td>
                        <td><?= $pet["raza"] ?></td>
                        <td><?= $pet["edad"] ?></td>
                        <td>
                            <button class="button" onclick="openModal(<?= $pet['id'] ?>)">Agregar Historial</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            </div>
            <div id="noMascotas" style="display:none; width:92%; max-width:1100px; margin:8px auto 0; padding:12px 14px; background:#eef2ff; border:1px solid #c7d2fe; border-radius:10px; color:#1e3a8a; font-weight:600;">No se encontraron mascotas con ese criterio.</div>
        <?php endif; ?>
    </div>

        <!-- Modal -->
        <div id="myModal" class="modal" aria-hidden="true" role="dialog">
            <div class="modal-content" role="document">
                <div class="modal-header">
                    <h2>Agregar Historial Clínico</h2>
                    <span class="close" onclick="closeModal()" aria-label="Cerrar">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="post" action="guardar_historial.php">
                        <input type="hidden" id="modal_pet_id" name="pet_id">
                        <table class="form-table">
                            <tr>
                                <th><label for="fecha_consulta">Fecha de Consulta</label></th>
                                <td><input type="date" id="fecha_consulta" name="fecha_consulta" required></td>
                            </tr>
                            <tr>
                                <th><label for="motivo_consulta">Motivo de Consulta</label></th>
                                <td><textarea id="motivo_consulta" name="motivo_consulta" required></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="diagnostico">Diagnóstico</label></th>
                                <td><textarea id="diagnostico" name="diagnostico"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="procedimientos_realizados">Procedimientos Realizados</label></th>
                                <td><textarea id="procedimientos_realizados" name="procedimientos_realizados"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="historial_vacunacion">Historial de Vacunación</label></th>
                                <td><textarea id="historial_vacunacion" name="historial_vacunacion"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="alergias">Alergias</label></th>
                                <td><textarea id="alergias" name="alergias"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="medicamentos_actuales">Medicamentos Actuales</label></th>
                                <td><textarea id="medicamentos_actuales" name="medicamentos_actuales"></textarea></td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button" onclick="closeModal()" style="background:#6b7280">Cancelar</button>
                    <button type="submit" form="" class="button" name="save_history" onclick="document.querySelector('#myModal form')?.submit();">Guardar Historial</button>
                </div>
            </div>
        </div>
    <script>



        function openModal(petId) {
            const modal = document.getElementById("myModal");
            document.getElementById("modal_pet_id").value = petId;
            modal.style.display = "block";
            modal.setAttribute('aria-hidden','false');
        }

        function closeModal() {
            const modal = document.getElementById("myModal");
            modal.style.display = "none";
            modal.setAttribute('aria-hidden','true');
        }

        // Cerrar al hacer click fuera del contenido
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('myModal');
            if (e.target === modal) closeModal();
        });

        // Cerrar con Escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // --- Filtros Clientes ---
        const clientesTable = document.getElementById('clientesTable');
        const clientesRows = clientesTable ? Array.from(clientesTable.querySelectorAll('tbody tr, tr')).slice(1) : [];
        const clientesInput = document.getElementById('searchCliente');
        const clientesCounter = document.getElementById('clientesCounter');
        const noClientes = document.getElementById('noClientes');

        function aplicarFiltroClientes(){
            const q = (clientesInput?.value || '').toLowerCase().trim();
            let visibles = 0;
            clientesRows.forEach(r => {
                const cols = r.querySelectorAll('td');
                const nombre = (cols[0]?.innerText || '').toLowerCase();
                const correo = (cols[1]?.innerText || '').toLowerCase();
                const match = !q || nombre.includes(q) || correo.includes(q);
                r.style.display = match ? '' : 'none';
                if (match) visibles++;
            });
            if (clientesCounter) clientesCounter.textContent = `Mostrando ${visibles} de ${clientesRows.length}`;
            if (noClientes) noClientes.style.display = visibles ? 'none' : 'block';
        }
        document.getElementById('applyFilterClientes')?.addEventListener('click', aplicarFiltroClientes);
        document.getElementById('clearFilterClientes')?.addEventListener('click', ()=>{ if (clientesInput){ clientesInput.value=''; aplicarFiltroClientes(); }});
        clientesInput?.addEventListener('keyup', (e)=>{ if (e.key==='Enter') aplicarFiltroClientes(); });

        // --- Filtros Mascotas ---
        const mascotasTable = document.getElementById('mascotasTable');
        const mascotasRows = mascotasTable ? Array.from(mascotasTable.querySelectorAll('tbody tr, tr')).slice(1) : [];
        const mascotasInput = document.getElementById('searchMascota');
        const mascotasCounter = document.getElementById('mascotasCounter');
        const noMascotas = document.getElementById('noMascotas');

        function aplicarFiltroMascotas(){
            const q = (mascotasInput?.value || '').toLowerCase().trim();
            let visibles = 0;
            mascotasRows.forEach(r => {
                const cols = r.querySelectorAll('td');
                const nombre = (cols[0]?.innerText || '').toLowerCase();
                const raza = (cols[2]?.innerText || '').toLowerCase();
                const match = !q || nombre.includes(q) || raza.includes(q);
                r.style.display = match ? '' : 'none';
                if (match) visibles++;
            });
            if (mascotasCounter) mascotasCounter.textContent = `Mostrando ${visibles} de ${mascotasRows.length}`;
            if (noMascotas) noMascotas.style.display = visibles ? 'none' : 'block';
        }
        document.getElementById('applyFilterMascotas')?.addEventListener('click', aplicarFiltroMascotas);
        document.getElementById('clearFilterMascotas')?.addEventListener('click', ()=>{ if (mascotasInput){ mascotasInput.value=''; aplicarFiltroMascotas(); }});
        mascotasInput?.addEventListener('keyup', (e)=>{ if (e.key==='Enter') aplicarFiltroMascotas(); });
    </script>
</body>
</html>