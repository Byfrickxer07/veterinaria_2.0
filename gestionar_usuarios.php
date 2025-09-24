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
$deleteId = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["confirm_delete"])) {
        $id = $_POST["confirm_delete"];
        $sql = "DELETE FROM user WHERE id = $id";
        if ($conn->query($sql) === TRUE) {
            $alertMessage = "¡Usuario eliminado exitosamente!";
        } else {
            $alertMessage = "Error al eliminar usuario: " . $conn->error;
        }
        $deleteId = null;
    } elseif (isset($_POST["edit"])) {
        $id = $_POST["id"];
        $nombre_usuario = $_POST["nombre_usuario"];
        $correo_electronico = $_POST["correo_electronico"];

        $duplicateCheckUsername = "SELECT id FROM user WHERE nombre_usuario='$nombre_usuario' AND id != $id";
        $resultUsername = $conn->query($duplicateCheckUsername);
        if ($resultUsername->num_rows > 0) {
            $alertMessage = "¡El nombre de usuario ya está en uso!";
        } else {
           
            $duplicateCheckEmail = "SELECT id FROM user WHERE correo_electronico='$correo_electronico' AND id != $id";
            $resultEmail = $conn->query($duplicateCheckEmail);
            if ($resultEmail->num_rows > 0) {
                $alertMessage = "¡El correo electrónico ya está en uso!";
            } else {
                $sql = "UPDATE user SET nombre_usuario='$nombre_usuario', correo_electronico='$correo_electronico' WHERE id=$id";
                if ($conn->query($sql) === TRUE) {
                    $alertMessage = "¡Usuario actualizado exitosamente!";
                } else {
                    $alertMessage = "Error al actualizar usuario: " . $conn->error;
                }
            }
        }
    }
}

$sql = "SELECT id, nombre_usuario, correo_electronico, rol FROM user WHERE rol != 'admin'";
$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
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
            position: fixed; 
            top: 0;
            left: 0;
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
            margin-left: 275px; 
            padding: 50px 20px; 
            text-align: center;
            height: 100vh;
            transition: padding 0.3s;
        }

        h1 {
            margin-top: 0;
        }

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

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .button {
            background-color: #03879C;
            color: #fff;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }

        .button:hover {
            background-color: #026b7c;
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
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .modal-header {
            padding: 2px 16px;
            background-color: #03879C;
            color: white;
        }

        .modal-body {
            padding: 2px 16px;
        }

        .modal-footer {
            padding: 2px 16px;
            background-color: #03879C;
            color: white;
        }

        .close {
            color: white;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
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
        <div class="toggle-menu">
            <i class='bx bx-chevron-left'></i>
        </div>
        <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Inicio</span></a>
        
        <a href="gestionar_turnos.php"><i class='bx bx-calendar'></i><span> Gestión de Turnos</span></a>
        <div class="bottom-menu">
            <a href="logout.php"><i class='bx bx-log-out'></i> <span>Logout</span></a>
        </div>
    </div>

    <div class="content">
        
        <?php if ($alertMessage): ?>
            <div class="alert <?= strpos($alertMessage, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <?= $alertMessage ?>
            </div>
        <?php endif; ?>

        <h1>Gestión de Usuarios</h1>

        <table>
            <tr>
                <th>ID</th>
                <th>Nombre de Usuario</th>
                <th>Correo Electrónico</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row["id"] ?></td>
                        <td><?= $row["nombre_usuario"] ?></td>
                        <td><?= $row["correo_electronico"] ?></td>
                        <td><?= $row["rol"] ?></td>
                        <td>
                            <button class="button" onclick="openEditModal(<?= $row['id'] ?>, '<?= $row['nombre_usuario'] ?>', '<?= $row['correo_electronico'] ?>')">Editar</button>
                            <button class="button" onclick="openDeleteModal(<?= $row['id'] ?>)">Eliminar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No se encontraron usuarios.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

   
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h2>Editar Usuario</h2>
            </div>
            <div class="modal-body">
                <form id="editForm" method="post">
                    <input type="hidden" name="id" id="editId">
                    <label for="editNombreUsuario">Nombre de Usuario:</label>
                    <input type="text" id="editNombreUsuario" name="nombre_usuario" required>
                    <label for="editCorreoElectronico">Correo Electrónico:</label>
                    <input type="email" id="editCorreoElectronico" name="correo_electronico" required>
                    <input type="submit" name="edit" value="Actualizar" class="button">
                </form>
            </div>
        </div>
    </div>

  
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeDeleteModal()">&times;</span>
                <h2>Eliminar Usuario</h2>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este usuario?</p>
                <form id="deleteForm" method="post">
                    <input type="hidden" name="confirm_delete" id="deleteId">
                    <input type="submit" value="Eliminar" class="button">
                </form>
                <button class="button" onclick="closeDeleteModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-menu').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        document.getElementById('darkModeSwitch').addEventListener('change', function() {
            document.body.classList.toggle('dark-mode', this.checked);
        });

        function openEditModal(id, nombre_usuario, correo_electronico) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombreUsuario').value = nombre_usuario;
            document.getElementById('editCorreoElectronico').value = correo_electronico;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openDeleteModal(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
    </script>
</body>
</html>
