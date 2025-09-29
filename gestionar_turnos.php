<?php
session_start();


$mysqli = new mysqli("localhost", "root", "", "veterinaria");


if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    die("Debe iniciar sesión para acceder a esta página.");
}



$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $tipo_servicio = $_POST['tipo_servicio'] ?? '';
        $cliente_id = (int)($_POST['cliente_id'] ?? 0);
        $mascota_id = (int)($_POST['mascota_id'] ?? 0);

        // Validar hora de 08 a 18
        $horaObj = new DateTime($hora);
        $hora_inicio = new DateTime('08:00:00');
        $hora_fin = new DateTime('18:00:00');
        $hora_str = $horaObj->format('H:i:s');
        if ($hora_str < $hora_inicio->format('H:i:s') || $hora_str > $hora_fin->format('H:i:s')) {
            $message = "La hora debe estar entre 08:00 y 18:00.";
            $message_type = "error";
        } else {
            // Chequear duplicado
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM turnos WHERE fecha = ? AND hora = ? AND tipo_servicio = ? AND user_id = ? AND mascota_id = ?");
            $stmt->bind_param('sssii', $fecha, $hora_str, $tipo_servicio, $cliente_id, $mascota_id);
            $stmt->execute();
            $stmt->bind_result($cnt);
            $stmt->fetch();
            $stmt->close();
            if ($cnt > 0) {
                $message = "Ya existe un turno con la misma fecha, hora, servicio y cliente.";
                $message_type = "error";
            } else {
                $stmt = $mysqli->prepare("INSERT INTO turnos (fecha, hora, tipo_servicio, estado, user_id, mascota_id) VALUES (?, ?, ?, 'pendiente', ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('sssii', $fecha, $hora_str, $tipo_servicio, $cliente_id, $mascota_id);
                    if ($stmt->execute()) {
                        $message = "Turno creado correctamente.";
                        $message_type = "success";
                    } else {
                        $message = "Error creando el turno: " . $mysqli->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Error al preparar la creación del turno.";
                    $message_type = "error";
                }
            }
        }
    }

    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $tipo_servicio = $_POST['tipo_servicio'];
        
       
        $hora = new DateTime($hora);
        $hora_inicio = new DateTime('08:00:00');
        $hora_fin = new DateTime('18:00:00');
        
       
        $hora_str = $hora->format('H:i:s');
        $hora_inicio_str = $hora_inicio->format('H:i:s');
        $hora_fin_str = $hora_fin->format('H:i:s');
        
        if ($hora_str < $hora_inicio_str || $hora_str > $hora_fin_str) {
            $message = "La hora debe estar entre 08:00 y 18:00.";
            $message_type = "error";
        } else {

            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM turnos WHERE fecha=? AND hora=? AND tipo_servicio=? AND id != ?");
            $stmt->bind_param('sssi', $fecha, $hora_str, $tipo_servicio, $id);
            $stmt->execute();
            $stmt->bind_result($dup);
            $stmt->fetch();
            $stmt->close();

            if ($dup > 0) {
                $message = "Ya existe un turno con la misma fecha, hora y servicio.";
                $message_type = "error";
            } else {
                $stmt = $mysqli->prepare("UPDATE turnos SET fecha=?, hora=?, tipo_servicio=? WHERE id=?");
                $stmt->bind_param('sssi', $fecha, $hora_str, $tipo_servicio, $id);
                if ($stmt->execute()) {
                    $message = "Turno actualizado correctamente.";
                    $message_type = "success";
                } else {
                    $message = "Error actualizando el turno: " . $mysqli->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['confirm_delete'])) {
        $id = (int)$_POST['confirm_delete'];
        // Eliminar físicamente el turno
        $stmt = $mysqli->prepare("DELETE FROM turnos WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = "Turno eliminado correctamente.";
                $message_type = "success";
            } else {
                $message = "Error eliminando el turno: " . $mysqli->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "No se pudo preparar la eliminación del turno.";
            $message_type = "error";
        }
    }

    // Reasignación de turno entre veterinarios (requiere columna doctor_id en turnos)
    if (isset($_POST['reassign'])) {
        $id = (int)($_POST['id'] ?? 0);
        $doctor_id = (int)($_POST['doctor_id'] ?? 0);
        if ($id > 0 && $doctor_id > 0) {
            $stmt = $mysqli->prepare("UPDATE turnos SET doctor_id=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('ii', $doctor_id, $id);
                if ($stmt->execute()) {
                    $message = "Turno reasignado correctamente.";
                    $message_type = "success";
                } else {
                    $message = "No se pudo reasignar el turno. Verifique que exista la columna doctor_id en la tabla turnos.";
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "No se pudo preparar la reasignación. Es posible que la columna doctor_id no exista.";
                $message_type = "error";
            }
        }
    }
}

// Auto-completar: cualquier turno 'pendiente' en el pasado pasa a 'completado'
// Requiere columna 'estado' en la tabla turnos
@$mysqli->query("UPDATE turnos 
                 SET estado='completado' 
                 WHERE estado='pendiente' 
                   AND (fecha < CURDATE() OR (fecha = CURDATE() AND hora < CURTIME()))");

// Listado de turnos (ejecutar SIEMPRE después de procesar POST)
$sql = "SELECT t.id, t.fecha, t.hora, t.tipo_servicio, t.estado, m.nombre AS mascota, u.nombre_usuario AS cliente
        FROM turnos t
        JOIN mascotas m ON t.mascota_id = m.id
        JOIN user u ON t.user_id = u.id";
$result = $mysqli->query($sql);

// Listas para selects
$doctores = $mysqli->query("SELECT id, nombre_usuario FROM user WHERE rol='doctor' ORDER BY nombre_usuario ASC");
$clientes = $mysqli->query("SELECT id, nombre_usuario FROM user WHERE rol='cliente' ORDER BY nombre_usuario ASC");
$mascotas = $mysqli->query("SELECT id, nombre FROM mascotas ORDER BY nombre ASC");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Panel de Usuario</title>
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
            padding-bottom: 60px;
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

        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #03879C;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .button {
            background-color: #03879C;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .button:hover {
            background-color: #026a80;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border: none;
            width: 90%;
            max-width: 560px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .modal-header, .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header {
            padding: 16px 20px;
            background-color: #027a8d;
            color: #fff;
        }

        .modal-header h2 {
            margin: 0;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-body label {
            display: block;
            margin: 10px 0 6px;
            font-weight: 600;
        }

        .modal-body input[type="text"],
        .modal-body input[type="date"],
        .modal-body input[type="time"],
        .modal-body select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd4dd;
            border-radius: 12px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .modal-body input:focus,
        .modal-body select:focus {
            border-color: #027a8d;
            box-shadow: 0 0 0 3px rgba(2, 122, 141, 0.15);
        }

        .modal-footer {
            padding: 12px 18px;
            background-color: #f0f4f8;
            color: #333;
            gap: 8px;
        }

        .modal-footer button,
        .save-button,
        .close-button {
            padding: 10px 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
        }

        .close-button {
            background-color: #ff5f5f;
            color: white;
        }

        .save-button {
            background-color: #027a8d;
            color: white;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="toggle-menu" onclick="toggleSidebar()">
        <i class='bx bx-chevron-left'></i>
        </div>
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Foto de Perfil" class="profile-image">
         
        </div>
        <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Inicio</span></a>
        <a href="gestionar_usuarios.php"><i class='bx bx-user'></i><span>Gestion Usuarios</span></a>
        <a href="gestionar_mascotas.php"><i class='bx bx-bone'></i><span>Gestionar Mascotas</span></a>
       
     
        <div class="bottom-menu">
        <a href="index.php"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>

    <div class="content">
        <h1>Panel de Usuario</h1>
        
        <?php if ($message != ""): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="container">
            <h2>Gestión de turnos</h2>
            <p>Ver, crear, modificar o cancelar turnos. Reasignar turnos entre veterinarios.</p>
            <button class="button" onclick="openCreateModal()">Crear Turno</button>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Servicio</th>
                        <th>Mascota</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['fecha']; ?></td>
                            <td><?php echo $row['hora']; ?></td>
                            <td><?php echo $row['tipo_servicio']; ?></td>
                            <td><?php echo $row['mascota']; ?></td>
                            <td><?php echo $row['cliente']; ?></td>
                            <td><?php echo isset($row['estado']) ? htmlspecialchars($row['estado']) : 'pendiente'; ?></td>
                            <td>
                                <button class="button" onclick="openEditModal(<?php echo $row['id']; ?>)">Editar</button>
                                <button class="button" onclick="confirmDelete(<?php echo $row['id']; ?>)">Cancelar</button>
                                <button class="button" onclick="openReassignModal(<?php echo $row['id']; ?>)">Reasignar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Turno</h2>
            </div>
            <form method="POST">
                <input type="hidden" id="editId" name="id">
                <div class="modal-body">
                    <label for="editFecha">Fecha:</label>
                    <input type="date" id="editFecha" name="fecha" required>

                    <label for="editTipoServicio">Tipo de Servicio:</label>
                    <select id="editTipoServicio" name="tipo_servicio" required>
                        <option value="">Selecciona un servicio</option>
                        <option value="vacunacion">Vacunación</option>
                        <option value="Control">Control</option>
                        <option value="castracion">Castración</option>
                        <option value="baño">Baño</option>
                    </select>

                    <label for="editHora">Hora:</label>
                    <select id="editHora" name="hora" required disabled>
                        <option value="">Selecciona el horario</option>
                    </select>
                    <small class="text-muted">Los horarios disponibles se actualizan según el servicio seleccionado</small>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="close-button">Cancelar</button>
                    <button type="submit" name="edit" class="save-button">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmar Eliminación</h2>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cancelar este turno?</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeDeleteConfirmModal()" class="close-button">Cancelar</button>
                <form method="POST">
                    <input type="hidden" id="deleteId" name="confirm_delete">
                    <button type="submit" class="save-button">Confirmar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Crear Turno -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Crear Turno</h2>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <label for="createFecha">Fecha:</label>
                    <input type="date" id="createFecha" name="fecha" required>

                    <label for="createTipoServicio">Tipo de Servicio:</label>
                    <select id="createTipoServicio" name="tipo_servicio" required>
                        <option value="">Selecciona un servicio</option>
                        <option value="vacunacion">Vacunación</option>
                        <option value="Control">Control</option>
                        <option value="castracion">Castración</option>
                        <option value="baño">Baño</option>
                    </select>

                    <label for="createHora">Hora:</label>
                    <select id="createHora" name="hora" required disabled>
                        <option value="">Selecciona el horario</option>
                    </select>
                    <small class="text-muted">Los horarios disponibles se actualizan según el servicio seleccionado</small>

                    <label for="createCliente">Cliente:</label>
                    <select id="createCliente" name="cliente_id" required>
                        <option value="">Seleccione un cliente</option>
                        <?php if ($clientes) { while($c = $clientes->fetch_assoc()) { ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_usuario']) ?></option>
                        <?php } } ?>
                    </select>

                    <label for="createMascota">Mascota:</label>
                    <select id="createMascota" name="mascota_id" required disabled>
                        <option value="">Seleccione primero un cliente</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeCreateModal()" class="close-button">Cancelar</button>
                    <button type="submit" name="create" class="save-button">Crear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reasignar Turno -->
    <div id="reassignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reasignar Turno</h2>
            </div>
            <form method="POST">
                <input type="hidden" id="reassignId" name="id">
                <div class="modal-body">
                    <label for="reassignDoctor">Veterinario:</label>
                    <select id="reassignDoctor" name="doctor_id" required>
                        <option value="">Seleccione un veterinario</option>
                        <?php if ($doctores) { while($d = $doctores->fetch_assoc()) { ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre_usuario']) ?></option>
                        <?php } } ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeReassignModal()" class="close-button">Cancelar</button>
                    <button type="submit" name="reassign" class="save-button">Reasignar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id) {
            document.getElementById('editId').value = id;
            const row = event.target.closest('tr');
            const fecha = row.cells[0].innerText.trim();
            const hora = row.cells[1].innerText.trim();
            const servicio = row.cells[2].innerText.trim();
            const editFecha = document.getElementById('editFecha');
            const editServicio = document.getElementById('editTipoServicio');
            const editHora = document.getElementById('editHora');
            editFecha.value = fecha;
            editServicio.value = servicio;
            updateEditTimeSlots();
            // Seleccionar la hora existente si está en la lista
            setTimeout(() => {
                for (const opt of editHora.options) {
                    if (opt.value === hora) { editHora.value = hora; break; }
                }
            }, 0);
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteConfirmModal').style.display = 'block';
        }

        function closeDeleteConfirmModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }

        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
        }

        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function openReassignModal(id) {
            document.getElementById('reassignId').value = id;
            document.getElementById('reassignModal').style.display = 'block';
        }

        function closeReassignModal() {
            document.getElementById('reassignModal').style.display = 'none';
        }

        // Configuración de horarios según el tipo de servicio (como en la parte de usuario)
        const serviceTimeSlots = {
            'vacunacion': 20,
            'Control': 20,
            'castracion': 60,
            'baño': 60
        };

        function updateCreateTimeSlots() {
            const serviceType = document.getElementById('createTipoServicio')?.value;
            const timeSelect = document.getElementById('createHora');
            if (!timeSelect) return;
            // Limpiar opciones existentes
            timeSelect.innerHTML = '';

            if (!serviceType) {
                timeSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Selecciona el horario';
                timeSelect.appendChild(opt);
                return;
            }

            const interval = serviceTimeSlots[serviceType] || 30; // fallback 30m
            timeSelect.disabled = false;

            const startHour = 8; // 08:00
            const endHour = 18;  // 18:00
            for (let hour = startHour; hour < endHour; hour++) {
                for (let minute = 0; minute < 60; minute += interval) {
                    if (hour === endHour - 1 && minute + interval > 60) break;
                    const hh = hour.toString().padStart(2, '0');
                    const mm = minute.toString().padStart(2, '0');
                    const option = document.createElement('option');
                    option.value = `${hh}:${mm}`;
                    option.textContent = `${hh}:${mm}`;
                    timeSelect.appendChild(option);
                }
            }
            if (timeSelect.options.length > 0) {
                timeSelect.value = timeSelect.options[0].value;
            }
        }

        function updateEditTimeSlots() {
            const serviceType = document.getElementById('editTipoServicio')?.value;
            const timeSelect = document.getElementById('editHora');
            if (!timeSelect) return;
            timeSelect.innerHTML = '';
            if (!serviceType) {
                timeSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Selecciona el horario';
                timeSelect.appendChild(opt);
                return;
            }
            const interval = serviceTimeSlots[serviceType] || 30;
            timeSelect.disabled = false;
            const startHour = 8;
            const endHour = 18;
            for (let hour = startHour; hour < endHour; hour++) {
                for (let minute = 0; minute < 60; minute += interval) {
                    if (hour === endHour - 1 && minute + interval > 60) break;
                    const hh = hour.toString().padStart(2, '0');
                    const mm = minute.toString().padStart(2, '0');
                    const option = document.createElement('option');
                    option.value = `${hh}:${mm}`;
                    option.textContent = `${hh}:${mm}`;
                    timeSelect.appendChild(option);
                }
            }
        }

        // Fecha mínima hoy y eventos
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('createFecha');
            if (fechaInput) {
                const hoy = new Date().toISOString().split('T')[0];
                fechaInput.min = hoy;
                if (!fechaInput.value) fechaInput.value = hoy;
            }
            const tipoSelect = document.getElementById('createTipoServicio');
            if (tipoSelect) {
                tipoSelect.addEventListener('change', updateCreateTimeSlots);
            }
            const editTipoSelect = document.getElementById('editTipoServicio');
            if (editTipoSelect) {
                editTipoSelect.addEventListener('change', updateEditTimeSlots);
            }

            // Dependencia Mascota <- Cliente
            const clienteSelect = document.getElementById('createCliente');
            const mascotaSelect = document.getElementById('createMascota');
            if (clienteSelect && mascotaSelect) {
                clienteSelect.addEventListener('change', async function() {
                    const userId = this.value;
                    mascotaSelect.innerHTML = '';
                    if (!userId) {
                        mascotaSelect.disabled = true;
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'Seleccione primero un cliente';
                        mascotaSelect.appendChild(opt);
                        return;
                    }
                    try {
                        const resp = await fetch(`get_mascotas.php?user_id=${encodeURIComponent(userId)}`);
                        const data = await resp.json();
                        mascotaSelect.disabled = false;
                        const def = document.createElement('option');
                        def.value = '';
                        def.textContent = 'Seleccione una mascota';
                        mascotaSelect.appendChild(def);
                        if (data && Array.isArray(data.mascotas)) {
                            data.mascotas.forEach(m => {
                                const o = document.createElement('option');
                                o.value = m.id;
                                o.textContent = m.nombre;
                                mascotaSelect.appendChild(o);
                            });
                        }
                    } catch (e) {
                        mascotaSelect.disabled = true;
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'Error cargando mascotas';
                        mascotaSelect.appendChild(opt);
                    }
                });
            }
        });
    </script>
<?php $mysqli->close(); ?>
</body>
</html>
