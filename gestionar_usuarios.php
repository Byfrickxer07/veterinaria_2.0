<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$alertMessage = '';

function sanitize($v) { return trim($v ?? ''); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Eliminar usuario
    if (isset($_POST["confirm_delete"])) {
        $id = (int)$_POST["confirm_delete"];
        $stmt = $conn->prepare("DELETE FROM user WHERE id = ? AND rol != 'admin'");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $alertMessage = "¡Usuario eliminado exitosamente!";
            } else {
                $alertMessage = "Error al eliminar usuario.";
            }
            $stmt->close();
        } else {
            $alertMessage = "Error al preparar la eliminación.";
        }
    }
    // Crear usuario
    elseif (isset($_POST["create"])) {
        $nombre_usuario = sanitize($_POST["nombre_usuario"]);
        $apellido = sanitize($_POST["apellido"]);
        $correo_electronico = sanitize($_POST["correo_electronico"]);
        $contrasena = $_POST["contrasena"] ?? '';
        $telefono = sanitize($_POST["telefono"]);
        $dni = sanitize($_POST["dni"]);
        $rol = sanitize($_POST["rol"]);

        // Validaciones
        if ($nombre_usuario === '' || strlen($nombre_usuario) < 2) {
            $alertMessage = "El nombre de usuario debe tener al menos 2 caracteres.";
        } elseif ($apellido === '' || strlen($apellido) < 2) {
            $alertMessage = "El apellido debe tener al menos 2 caracteres.";
        } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = "Correo electrónico no válido.";
        } elseif (strlen($contrasena) < 8 || !preg_match('/[A-Z]/', $contrasena) || !preg_match('/\d/', $contrasena)) {
            $alertMessage = "La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.";
        } elseif ($dni !== '' && !preg_match('/^\d+$/', $dni)) {
            $alertMessage = "El DNI solo puede contener números.";
        } elseif ($telefono !== '' && !preg_match('/^\d+$/', $telefono)) {
            $alertMessage = "El teléfono solo puede contener números.";
        } elseif (strlen($dni) > 15 || strlen($telefono) > 15) {
            $alertMessage = "El DNI y el teléfono no pueden superar 15 dígitos.";
        } elseif (!in_array($rol, ['doctor','cliente','admin'])) {
            $alertMessage = "Rol inválido.";
        } else {
            // Unicidad: correo, dni, telefono
            // Correo
            $stmt = $conn->prepare("SELECT id FROM user WHERE correo_electronico = ? LIMIT 1");
            $stmt->bind_param("s", $correo_electronico);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) { $alertMessage = "El correo electrónico ya está en uso."; }
            $stmt->close();

            // DNI
            if ($alertMessage === '' && $dni !== '') {
                $stmt = $conn->prepare("SELECT id FROM user WHERE dni = ? LIMIT 1");
                $stmt->bind_param("s", $dni);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) { $alertMessage = "El DNI ya está en uso."; }
                $stmt->close();
            }
            // Teléfono
            if ($alertMessage === '' && $telefono !== '') {
                $stmt = $conn->prepare("SELECT id FROM user WHERE telefono = ? LIMIT 1");
                $stmt->bind_param("s", $telefono);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) { $alertMessage = "El teléfono ya está en uso."; }
                $stmt->close();
            }

            if ($alertMessage === '') {
                $hashed = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (nombre_usuario, apellido, correo_electronico, contrasena, telefono, dni, rol) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssssss", $nombre_usuario, $apellido, $correo_electronico, $hashed, $telefono, $dni, $rol);
                    if ($stmt->execute()) {
                        $alertMessage = "¡Usuario creado exitosamente!";
                    } else {
                        $alertMessage = "Error al crear usuario.";
                    }
                    $stmt->close();
                } else {
                    $alertMessage = "Error en la preparación del registro.";
                }
            }
        }
    }
    // Editar usuario
    elseif (isset($_POST["edit"])) {
        $id = (int)$_POST["id"];
        $nombre_usuario = sanitize($_POST["nombre_usuario"]);
        $apellido = sanitize($_POST["apellido"]);
        $correo_electronico = sanitize($_POST["correo_electronico"]);
        $telefono = sanitize($_POST["telefono"]);
        $dni = sanitize($_POST["dni"]);
        $rol = sanitize($_POST["rol"]);

        if ($nombre_usuario === '' || strlen($nombre_usuario) < 2) {
            $alertMessage = "El nombre de usuario debe tener al menos 2 caracteres.";
        } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = "Correo electrónico no válido.";
        } elseif ($apellido === '' || strlen($apellido) < 2) {
            $alertMessage = "El apellido debe tener al menos 2 caracteres.";
        } elseif ($dni !== '' && !preg_match('/^\d+$/', $dni)) {
            $alertMessage = "El DNI solo puede contener números.";
        } elseif ($telefono !== '' && !preg_match('/^\d+$/', $telefono)) {
            $alertMessage = "El teléfono solo puede contener números.";
        } elseif (strlen($dni) > 15 || strlen($telefono) > 15) {
            $alertMessage = "El DNI y el teléfono no pueden superar 15 dígitos.";
        } elseif (!in_array($rol, ['doctor','cliente','admin'])) {
            $alertMessage = "Rol inválido.";
        } else {
            // Unicidad excluyendo el propio id
            // Correo
            $stmt = $conn->prepare("SELECT id FROM user WHERE correo_electronico = ? AND id != ? LIMIT 1");
            $stmt->bind_param("si", $correo_electronico, $id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) { $alertMessage = "El correo electrónico ya está en uso."; }
            $stmt->close();

            if ($alertMessage === '' && $dni !== '') {
                $stmt = $conn->prepare("SELECT id FROM user WHERE dni = ? AND id != ? LIMIT 1");
                $stmt->bind_param("si", $dni, $id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) { $alertMessage = "El DNI ya está en uso."; }
                $stmt->close();
            }
            if ($alertMessage === '' && $telefono !== '') {
                $stmt = $conn->prepare("SELECT id FROM user WHERE telefono = ? AND id != ? LIMIT 1");
                $stmt->bind_param("si", $telefono, $id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) { $alertMessage = "El teléfono ya está en uso."; }
                $stmt->close();
            }

            if ($alertMessage === '') {
                $stmt = $conn->prepare("UPDATE user SET nombre_usuario = ?, apellido = ?, correo_electronico = ?, telefono = ?, dni = ?, rol = ? WHERE id = ? AND rol != 'admin'");
                if ($stmt) {
                    $stmt->bind_param("ssssssi", $nombre_usuario, $apellido, $correo_electronico, $telefono, $dni, $rol, $id);
                    if ($stmt->execute()) {
                        $alertMessage = "¡Usuario actualizado exitosamente!";
                    } else {
                        $alertMessage = "Error al actualizar usuario.";
                    }
                    $stmt->close();
                } else {
                    $alertMessage = "Error en la preparación de la actualización.";
                }
            }
        }
    }
}

$sql = "SELECT id, nombre_usuario, apellido, correo_electronico, telefono, dni, rol FROM user WHERE rol != 'admin' ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
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
            transition: background-color 0.3s, padding 0.3s, transform 0.15s ease-in-out;
            box-sizing: border-box;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 15px;
        }

        .sidebar a:hover {
            background-color: #03485f;
            transform: translateY(-1px);
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
            margin-left: 275px; 
            padding: 40px 30px; 
            min-height: 100vh;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 24px;
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
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #E3F3F6;
            color: #333;
            font-weight: 600;
        }

        .button {
            background-color: #027a8d;
            color: #fff;
            border: none;
            padding: 10px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 4px;
            cursor: pointer;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: background-color 0.3s ease, transform 0.1s ease;
        }

        .button:hover {
            background-color: #03485f;
            transform: translateY(-1px);
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

        .modal-header {
            padding: 16px 20px;
            background-color: #027a8d;
            color: white;
        }

        .modal-body {
            padding: 20px;
        }

        /* Inputs y selects más amigables en los modales */
        .modal-body label {
            display: block;
            margin: 10px 0 6px;
            font-weight: 600;
        }

        .modal-body input[type="text"],
        .modal-body input[type="email"],
        .modal-body input[type="password"],
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
        }

        .close {
            color: #fff;
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
        <a href="gestionar_mascotas.php"><i class='bx bx-bone'></i><span> Gestionar Mascotas</span></a>
        <div class="bottom-menu">
            <a href="logout.php"><i class='bx bx-log-out'></i> <span>Cerrar Sesión</span></a>
        </div>
    </div>

    <div class="content">
        
        <?php if ($alertMessage): ?>
            <div class="alert <?= strpos($alertMessage, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <?= $alertMessage ?>
            </div>
        <?php endif; ?>

        <h1>Gestión de Usuarios</h1>
        <button class="button" onclick="openCreateModal()">Crear Usuario</button>

        <table>
            <tr>
                <th>ID</th>
                <th>Nombre de Usuario</th>
                <th>Apellido</th>
                <th>Correo Electrónico</th>
                <th>Teléfono</th>
                <th>DNI</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row["id"] ?></td>
                        <td><?= $row["nombre_usuario"] ?></td>
                        <td><?= $row["apellido"] ?></td>
                        <td><?= $row["correo_electronico"] ?></td>
                        <td><?= $row["telefono"] ?></td>
                        <td><?= $row["dni"] ?></td>
                        <td><?= $row["rol"] ?></td>
                        <td>
                            <button class="button" onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nombre_usuario'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['apellido'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['correo_electronico'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['telefono'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['dni'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['rol'], ENT_QUOTES) ?>')">Editar</button>
                            <button class="button" onclick="openDeleteModal(<?= $row['id'] ?>)">Eliminar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No se encontraron usuarios.</td>
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
                    <label for="editApellido">Apellido:</label>
                    <input type="text" id="editApellido" name="apellido" required>
                    <label for="editCorreoElectronico">Correo Electrónico:</label>
                    <input type="email" id="editCorreoElectronico" name="correo_electronico" required>
                    <label for="editTelefono">Teléfono:</label>
                    <input type="text" id="editTelefono" name="telefono" maxlength="15">
                    <label for="editDNI">DNI:</label>
                    <input type="text" id="editDNI" name="dni" maxlength="15">
                    <label for="editRol">Rol:</label>
                    <select id="editRol" name="rol" required>
                        <option value="doctor">Doctor</option>
                        <option value="cliente">Cliente</option>
                        <option value="admin">Admin</option>
                    </select>
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

    <!-- Crear usuario -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeCreateModal()">&times;</span>
                <h2>Crear Usuario</h2>
            </div>
            <div class="modal-body">
                <form id="createForm" method="post">
                    <label for="createNombreUsuario">Nombre de Usuario:</label>
                    <input type="text" id="createNombreUsuario" name="nombre_usuario" required>
                    <label for="createApellido">Apellido:</label>
                    <input type="text" id="createApellido" name="apellido" required>
                    <label for="createCorreoElectronico">Correo Electrónico:</label>
                    <input type="email" id="createCorreoElectronico" name="correo_electronico" required>
                    <label for="createContrasena">Contraseña:</label>
                    <input type="password" id="createContrasena" name="contrasena" required>
                    <label for="createTelefono">Teléfono:</label>
                    <input type="text" id="createTelefono" name="telefono" maxlength="15">
                    <label for="createDNI">DNI:</label>
                    <input type="text" id="createDNI" name="dni" maxlength="15">
                    <label for="createRol">Rol:</label>
                    <select id="createRol" name="rol" required>
                        <option value="doctor">Doctor</option>
                        <option value="cliente">Cliente</option>
                        <option value="admin">Admin</option>
                    </select>
                    <input type="submit" name="create" value="Crear" class="button">
                </form>
            </div>
        </div>
    </div>

    <script>
        // Sidebar siempre visible: no registrar eventos de colapso

        function openEditModal(id, nombre_usuario, apellido, correo_electronico, telefono, dni, rol) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombreUsuario').value = nombre_usuario;
            document.getElementById('editApellido').value = apellido;
            document.getElementById('editCorreoElectronico').value = correo_electronico;
            document.getElementById('editTelefono').value = telefono;
            document.getElementById('editDNI').value = dni;
            document.getElementById('editRol').value = rol;
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

        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
    </script>
</body>
</html>
