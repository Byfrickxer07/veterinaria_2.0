<?php
session_start();


$mysqli = new mysqli("localhost", "root", "", "veterinaria");


if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}


if (!isset($_SESSION['user_id'])) {
    die("Debe iniciar sesión para acceder a esta página.");
}

$sql = "SELECT t.id, t.fecha, t.hora, t.tipo_servicio, m.nombre AS mascota, u.nombre_usuario AS cliente
        FROM turnos t
        JOIN mascotas m ON t.mascota_id = m.id
        JOIN user u ON t.user_id = u.id";

$result = $mysqli->query($sql);

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

            $check_sql = "SELECT COUNT(*) as count
                          FROM turnos
                          WHERE fecha='$fecha' AND hora='$hora_str' AND tipo_servicio='$tipo_servicio' AND id != $id";
            
            $check_result = $mysqli->query($check_sql);
            $check_row = $check_result->fetch_assoc();
            
            if ($check_row['count'] > 0) {
                $message = "Ya existe un turno con la misma fecha, hora y servicio.";
                $message_type = "error";
            } else {
            
                $update_sql = "UPDATE turnos SET fecha='$fecha', hora='$hora_str', tipo_servicio='$tipo_servicio' WHERE id=$id";
                if ($mysqli->query($update_sql) === TRUE) {
                    $message = "Turno actualizado correctamente.";
                    $message_type = "success";
                } else {
                    $message = "Error actualizando el turno: " . $mysqli->error;
                    $message_type = "error";
                }
            }
        }
    }

    if (isset($_POST['confirm_delete'])) {
        $id = $_POST['confirm_delete'];

        
        $delete_sql = "DELETE FROM turnos WHERE id=$id";
        if ($mysqli->query($delete_sql) === TRUE) {
            $message = "Turno eliminado correctamente.";
            $message_type = "success";
        } else {
            $message = "Error eliminando el turno: " . $mysqli->error;
            $message_type = "error";
        }
    }
}

$mysqli->close();
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
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-header, .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-footer {
            margin-top: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .modal-footer button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-footer .close-button {
            background-color: #ff5f5f;
            color: white;
        }

        .modal-footer .save-button {
            background-color: #03879C;
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
            <h2>Listado de Turnos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Servicio</th>
                        <th>Mascota</th>
                        <th>Cliente</th>
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
                            <td>
                                <button class="button" onclick="openEditModal(<?php echo $row['id']; ?>)">Editar</button>
                                <button class="button" onclick="confirmDelete(<?php echo $row['id']; ?>)">Eliminar</button>
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
                <button onclick="closeEditModal()" class="close-button">X</button>
            </div>
            <form method="POST">
                <input type="hidden" id="editId" name="id">
                <div>
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="editFecha" name="fecha" required>
                </div>
                <div>
                    <label for="hora">Hora:</label>
                    <input type="time" id="editHora" name="hora" required>
                </div>
                <div>
                    <label for="tipo_servicio">Tipo de Servicio:</label>
                    <input type="text" id="editTipoServicio" name="tipo_servicio" required>
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
                <button onclick="closeDeleteConfirmModal()" class="close-button">X</button>
            </div>
            <p>¿Estás seguro de que deseas eliminar este turno?</p>
            <div class="modal-footer">
                <button type="button" onclick="closeDeleteConfirmModal()" class="close-button">Cancelar</button>
                <form method="POST">
                    <input type="hidden" id="deleteId" name="confirm_delete">
                    <button type="submit" class="save-button">Eliminar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(id) {
            document.getElementById('editId').value = id;
          
            const row = event.target.closest('tr');
            document.getElementById('editFecha').value = row.cells[0].innerText;
            document.getElementById('editHora').value = row.cells[1].innerText;
            document.getElementById('editTipoServicio').value = row.cells[2].innerText;
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
    </script>
</body>
</html>
