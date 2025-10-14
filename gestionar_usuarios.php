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
        if ($nombre_usuario === '' || strlen($nombre_usuario) < 4) {
            $alertMessage = "El nombre de usuario debe tener al menos 4 caracteres.";
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $nombre_usuario)) {
            $alertMessage = "El nombre de usuario solo puede contener letras y espacios.";
        } elseif (strlen($nombre_usuario) > 50) {
            $alertMessage = "El nombre de usuario no puede superar 50 caracteres.";
        } elseif ($apellido === '' || strlen($apellido) < 2) {
            $alertMessage = "El apellido debe tener al menos 2 caracteres.";
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $apellido)) {
            $alertMessage = "El apellido solo puede contener letras y espacios.";
        } elseif (strlen($apellido) > 50) {
            $alertMessage = "El apellido no puede superar 50 caracteres.";
        } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = "Correo electrónico no válido.";
        } elseif (strlen($contrasena) < 8 || !preg_match('/[A-Z]/', $contrasena) || !preg_match('/\d/', $contrasena)) {
            $alertMessage = "La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.";
        } elseif ($dni !== '' && !preg_match('/^\d+$/', $dni)) {
            $alertMessage = "El DNI solo puede contener números.";
        } elseif ($dni !== '' && strlen($dni) !== 8) {
            $alertMessage = "El DNI debe tener exactamente 8 dígitos.";
        } elseif ($telefono !== '' && !preg_match('/^\d+$/', $telefono)) {
            $alertMessage = "El teléfono solo puede contener números.";
        } elseif ($telefono !== '' && strlen($telefono) !== 10) {
            $alertMessage = "El teléfono debe tener exactamente 10 dígitos.";
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

        if ($nombre_usuario === '' || strlen($nombre_usuario) < 4) {
            $alertMessage = "El nombre de usuario debe tener al menos 4 caracteres.";
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $nombre_usuario)) {
            $alertMessage = "El nombre de usuario solo puede contener letras y espacios.";
        } elseif (strlen($nombre_usuario) > 50) {
            $alertMessage = "El nombre de usuario no puede superar 50 caracteres.";
        } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
            $alertMessage = "Correo electrónico no válido.";
        } elseif ($apellido === '' || strlen($apellido) < 2) {
            $alertMessage = "El apellido debe tener al menos 2 caracteres.";
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $apellido)) {
            $alertMessage = "El apellido solo puede contener letras y espacios.";
        } elseif (strlen($apellido) > 50) {
            $alertMessage = "El apellido no puede superar 50 caracteres.";
        } elseif ($dni !== '' && !preg_match('/^\d+$/', $dni)) {
            $alertMessage = "El DNI solo puede contener números.";
        } elseif ($dni !== '' && strlen($dni) !== 8) {
            $alertMessage = "El DNI debe tener exactamente 8 dígitos.";
        } elseif ($telefono !== '' && !preg_match('/^\d+$/', $telefono)) {
            $alertMessage = "El teléfono solo puede contener números.";
        } elseif ($telefono !== '' && strlen($telefono) !== 10) {
            $alertMessage = "El teléfono debe tener exactamente 10 dígitos.";
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

        .content {
            flex-grow: 1;
            margin-left: 275px; 
            padding: 40px 30px; 
            min-height: 100vh;
        }

        /* Toolbar de filtros/búsqueda */
        .table-toolbar { display:flex; gap:10px; align-items:center; padding:12px 16px; background:#f9fcfd; border:1px solid #e6f0f2; border-radius:14px; margin: 10px 0 16px; }
        .toolbar-input, .toolbar-select { padding:10px 12px; border:1px solid #ccd9de; border-radius:10px; background:#fff; font-size:14px; }
        .toolbar-input { flex:1; }
        .toolbar-button { background: linear-gradient(135deg,#027a8d,#025162); color:#fff; border:none; padding:10px 14px; border-radius:10px; font-weight:700; cursor:pointer; box-shadow:0 4px 12px rgba(2,122,141,0.2); }
        .toolbar-button.secondary { background:linear-gradient(135deg,#6b7280,#374151); }
        .toolbar-button:hover { filter:brightness(1.05); transform: translateY(-1px); }

        /* Badges y acciones modernas */
        .badge { display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; }
        .badge-admin { background:#EDE9FE; color:#5B21B6; border:1px solid #DDD6FE; }
        .badge-doctor { background:#E0F2FE; color:#0369A1; border:1px solid #BAE6FD; }
        .badge-cliente { background:#DCFCE7; color:#065F46; border:1px solid #BBF7D0; }
        .actions .icon-btn { background:#fff; border:1px solid #e6f0f2; padding:8px 10px; border-radius:10px; cursor:pointer; box-shadow:0 2px 6px rgba(2,122,141,.08); }
        .actions .icon-btn:hover { box-shadow:0 4px 12px rgba(2,122,141,.15); transform: translateY(-1px); }

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

        .button-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .button-danger:hover {
            background-color: #c82333;
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
            background: rgba(0,0,0,0.35);
            backdrop-filter: blur(2px);
            padding: 24px 12px;
        }

        .modal-content {
            background-color: #fff;
            margin: 40px auto;
            padding: 0;
            border: none;
            width: 92%;
            max-width: 650px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(2,122,141,0.25);
            transform: translateY(10px);
            animation: modalIn .3s ease-out;
        }

        @keyframes modalIn { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0);} }

        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg,#027a8d,#035c6b);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: white;
        }

        .modal-close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        .modal-body {
            padding: 30px;
            background: #fafbfc;
        }

        /* Inputs y selects más amigables en los modales */
        .modal-body label {
            display: block;
            margin: 15px 0 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .modal-body input[type="text"],
        .modal-body input[type="email"],
        .modal-body input[type="password"],
        .modal-body select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            box-sizing: border-box;
            outline: none;
            transition: all 0.2s ease;
            background: #fff;
            font-size: 14px;
            color: #374151;
        }

        .modal-body input:focus,
        .modal-body select:focus {
            border-color: #027a8d;
            box-shadow: 0 0 0 4px rgba(2, 122, 141, 0.1);
            background: #fff;
        }

        .modal-body input:hover,
        .modal-body select:hover {
            border-color: #9ca3af;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .modal-footer {
            padding: 12px 18px;
            background-color: #f0f4f8;
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background .2s ease;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Profile Image" class="profile-image">
            
        </div>
    
        <a href="admin_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
        <a href="gestionar_usuarios.php" class="active"><i class='bx bx-user'></i><span>Gestionar Usuarios</span></a>
        <a href="gestionar_turnos.php"><i class='bx bx-calendar'></i><span>Gestionar Turnos</span></a>
        <a href="gestionar_mascotas.php"><i class='bx bx-bone'></i><span>Gestionar Mascotas</span></a>
        <a href="adopcion_page.php?view=admin"><i class='bx bx-heart'></i><span>Adopción (Admin)</span></a>
        <div class="bottom-menu">
            <a href="index.php" id="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
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
                            <button class="button button-danger" onclick="openDeleteModal(<?= $row['id'] ?>)">Eliminar</button>
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
                <h2>Editar Usuario</h2>
                <button class="modal-close-btn" onclick="closeEditModal()">Cerrar</button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="post">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editNombreUsuario">Nombre de Usuario:</label>
                            <input type="text" id="editNombreUsuario" name="nombre_usuario" required>
                        </div>
                        <div class="form-group">
                            <label for="editApellido">Apellido:</label>
                            <input type="text" id="editApellido" name="apellido" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCorreoElectronico">Correo Electrónico:</label>
                        <input type="email" id="editCorreoElectronico" name="correo_electronico" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editTelefono">Teléfono:</label>
                            <input type="text" id="editTelefono" name="telefono" maxlength="10" pattern="[0-9]{10}" title="El teléfono debe tener exactamente 10 dígitos">
                        </div>
                        <div class="form-group">
                            <label for="editDNI">DNI:</label>
                            <input type="text" id="editDNI" name="dni" maxlength="8" pattern="[0-9]{8}" title="El DNI debe tener exactamente 8 dígitos">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editRol">Rol:</label>
                        <select id="editRol" name="rol" required>
                            <option value="doctor">Doctor</option>
                            <option value="cliente">Cliente</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px;">
                        <input type="submit" name="edit" value="Actualizar Usuario" class="button" style="padding: 12px 30px; font-size: 16px; font-weight: 600;">
                    </div>
                </form>
            </div>
        </div>
    </div>

  
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Eliminar Usuario</h2>
                <button class="modal-close-btn" onclick="closeDeleteModal()">Cerrar</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 20px 0;">
                    <p style="font-size: 16px; color: #374151; margin-bottom: 25px;">¿Estás seguro de que deseas eliminar este usuario?</p>
                    <form id="deleteForm" method="post" style="display: inline-block;">
                        <input type="hidden" name="confirm_delete" id="deleteId">
                        <input type="submit" value="Eliminar" class="button button-danger" style="margin-right: 10px;">
                    </form>
                    <button class="button" onclick="closeDeleteModal()">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Crear usuario -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Crear Usuario</h2>
                <button class="modal-close-btn" onclick="closeCreateModal()">Cerrar</button>
            </div>
            <div class="modal-body">
                <form id="createForm" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createNombreUsuario">Nombre de Usuario:</label>
                            <input type="text" id="createNombreUsuario" name="nombre_usuario" required>
                        </div>
                        <div class="form-group">
                            <label for="createApellido">Apellido:</label>
                            <input type="text" id="createApellido" name="apellido" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="createCorreoElectronico">Correo Electrónico:</label>
                        <input type="email" id="createCorreoElectronico" name="correo_electronico" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="createContrasena">Contraseña:</label>
                        <input type="password" id="createContrasena" name="contrasena" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="createTelefono">Teléfono:</label>
                            <input type="text" id="createTelefono" name="telefono" maxlength="10" pattern="[0-9]{10}" title="El teléfono debe tener exactamente 10 dígitos">
                        </div>
                        <div class="form-group">
                            <label for="createDNI">DNI:</label>
                            <input type="text" id="createDNI" name="dni" maxlength="8" pattern="[0-9]{8}" title="El DNI debe tener exactamente 8 dígitos">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="createRol">Rol:</label>
                        <select id="createRol" name="rol" required>
                            <option value="doctor">Doctor</option>
                            <option value="cliente">Cliente</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px;">
                        <input type="submit" name="create" value="Crear Usuario" class="button" style="padding: 12px 30px; font-size: 16px; font-weight: 600;">
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        // Validaciones del lado del cliente
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const nombreUsuario = form.querySelector('input[name="nombre_usuario"]').value.trim();
            const apellido = form.querySelector('input[name="apellido"]').value.trim();
            const telefono = form.querySelector('input[name="telefono"]').value.trim();
            const dni = form.querySelector('input[name="dni"]').value.trim();
            
            // Validar nombre de usuario
            if (nombreUsuario.length < 4) {
                Swal.fire('Error', 'El nombre de usuario debe tener al menos 4 caracteres.', 'error');
                return false;
            }
            if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u.test(nombreUsuario)) {
                Swal.fire('Error', 'El nombre de usuario solo puede contener letras y espacios.', 'error');
                return false;
            }
            if (nombreUsuario.length > 50) {
                Swal.fire('Error', 'El nombre de usuario no puede superar 50 caracteres.', 'error');
                return false;
            }
            
            // Validar apellido
            if (apellido.length < 2) {
                Swal.fire('Error', 'El apellido debe tener al menos 2 caracteres.', 'error');
                return false;
            }
            if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u.test(apellido)) {
                Swal.fire('Error', 'El apellido solo puede contener letras y espacios.', 'error');
                return false;
            }
            if (apellido.length > 50) {
                Swal.fire('Error', 'El apellido no puede superar 50 caracteres.', 'error');
                return false;
            }
            
            // Validar teléfono
            if (telefono !== '' && !/^\d{10}$/.test(telefono)) {
                Swal.fire('Error', 'El teléfono debe tener exactamente 10 dígitos.', 'error');
                return false;
            }
            
            // Validar DNI
            if (dni !== '' && !/^\d{8}$/.test(dni)) {
                Swal.fire('Error', 'El DNI debe tener exactamente 8 dígitos.', 'error');
                return false;
            }
            
            return true;
        }
        
        // Agregar validación a los formularios
        document.getElementById('editForm').addEventListener('submit', function(e) {
            if (!validateForm('editForm')) {
                e.preventDefault();
            }
        });
        
        document.getElementById('createForm').addEventListener('submit', function(e) {
            if (!validateForm('createForm')) {
                e.preventDefault();
            }
        });
        
        // Restricciones en tiempo real para teléfono y DNI
        document.querySelectorAll('input[name="telefono"]').forEach(input => {
            input.addEventListener('input', function(e) {
                // Solo permitir números
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                // Limitar a 10 dígitos
                if (e.target.value.length > 10) {
                    e.target.value = e.target.value.substring(0, 10);
                }
            });
        });
        
        document.querySelectorAll('input[name="dni"]').forEach(input => {
            input.addEventListener('input', function(e) {
                // Solo permitir números
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                // Limitar a 8 dígitos
                if (e.target.value.length > 8) {
                    e.target.value = e.target.value.substring(0, 8);
                }
            });
        });
        
        // Restricciones para nombres (solo letras y espacios)
        document.querySelectorAll('input[name="nombre_usuario"], input[name="apellido"]').forEach(input => {
            input.addEventListener('input', function(e) {
                // Solo permitir letras, espacios y caracteres especiales del español
                e.target.value = e.target.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, '');
                // Limitar longitud
                if (e.target.value.length > 50) {
                    e.target.value = e.target.value.substring(0, 50);
                }
            });
        });

        // Confirmación para cerrar sesión
        document.getElementById('logout-button').addEventListener('click', (e) => {
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: '¿Estás seguro de que deseas cerrar sesión?',
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
</body>
</html>
