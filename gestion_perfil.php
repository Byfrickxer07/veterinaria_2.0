<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=veterinaria", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener la información del usuario
    $query = "SELECT nombre_usuario, correo_electronico FROM user WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener las mascotas del usuario
    $query = "SELECT * FROM mascotas WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['edit_pet'])) {
            $pet_id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $especie = $_POST['especie'];
            $raza = $_POST['raza'];
            $edad = $_POST['edad'];
            
            $query = "UPDATE mascotas SET nombre = :nombre, especie = :especie, raza = :raza, edad = :edad WHERE id = :id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':especie', $especie);
            $stmt->bindParam(':raza', $raza);
            $stmt->bindParam(':edad', $edad);
            $stmt->bindParam(':id', $pet_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $mensaje = "Mascota actualizada con éxito.";
        } elseif (isset($_POST['delete_pet'])) {
            $pet_id = $_POST['id'];

            $query = "DELETE FROM mascotas WHERE id = :id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $pet_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $mensaje = "Mascota eliminada con éxito.";
        } elseif (isset($_POST['update_profile'])) {
            $nombre_usuario = $_POST['username'];
            $correo_electronico = $_POST['email'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];

            $query = "SELECT contrasena FROM user WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $usuario_data['contrasena'])) {
                $query = "UPDATE user SET nombre_usuario = :nombre_usuario, correo_electronico = :correo_electronico";
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query .= ", contrasena = :new_password";
                }
                $query .= " WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':nombre_usuario', $nombre_usuario);
                $stmt->bindParam(':correo_electronico', $correo_electronico);
                if (!empty($new_password)) {
                    $stmt->bindParam(':new_password', $hashed_password);
                }
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();

                $mensaje = "Perfil actualizado con éxito.";
            } else {
                $mensaje = "La contraseña actual es incorrecta.";
            }
        }
    }
} catch (PDOException $e) {
    $mensaje = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Perfil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #027a8d;
            font-size: 32px;
            text-align: center;
            margin-top: 20px;
        }
        h2 {
            color: #027a8d;
            font-size: 24px;
            margin-top: 20px;
            border-bottom: 2px solid #027a8d;
            padding-bottom: 10px;
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
            color: #027a8d;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #027a8d;
        }
        button {
            background-color: #027a8d;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #025b6c;
        }
        .message,
        .error {
            text-align: center;
            margin-top: 10px;
            font-size: 16px;
        }
        .message {
            color: green;
        }
        .error {
            color: red;
        }
        .return-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #027a8d;
            text-decoration: none;
            font-size: 16px;
        }
        .return-link:hover {
            color: #025b6c;
        }
        .pet-list {
            list-style: none;
            padding: 0;
        }
        .pet-list li {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }
        .pet-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .pet-actions form {
            display: flex;
            align-items: center;
            margin: 0;
        }
        .pet-actions button {
            font-size: 14px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .pet-actions button:hover {
            background-color: #025b6c;
        }
        .pet-actions .edit-button {
            background-color: #027a8d;
            color: #fff;
            padding: 6px 10px;
        }
        .pet-actions .edit-button:hover {
            background-color: #025b6c;
        }
        .pet-actions .delete-button {
            background-color: #d9534f;
            color: #fff;
        }
        .pet-actions .delete-button:hover {
            background-color: #c9302c;
        }
        .pet-info {
            margin-bottom: 10px;
        }
        .pet-edit-fields {
            display: flex;
            flex-direction: column;
        }
        .pet-edit-fields label {
            margin-bottom: 4px;
        }
        .pet-edit-fields input {
            margin-bottom: 8px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            border-radius: 5px;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #d9534f;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
        }
        .modal-close:hover {
            background: #c9302c;
        }
    </style>
    <script>
        function openModal(id, nombre, especie, raza, edad) {
            document.getElementById('edit-pet-id').value = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-especie').value = especie;
            document.getElementById('edit-raza').value = raza;
            document.getElementById('edit-edad').value = edad;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</head>
<body>





    <div class="container">
        <h1>Perfil del Usuario</h1>
        <?php if (isset($mensaje)): ?>
            <div class="<?php echo strpos($mensaje, 'Error') === false ? 'message' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        <form action="gestion_perfil.php" method="post">
            <h2>Actualizar Perfil</h2>
            <div class="form-group">
                <label for="username">Nombre de Usuario:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" required>
            </div>
            <div class="form-group">
                <label for="current_password">Contraseña Actual:</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            <div class="form-group">
                <label for="new_password">Nueva Contraseña:</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <button type="submit" name="update_profile">Actualizar Perfil</button>
        </form>

        <h2>Mis Mascotas</h2>
        <ul class="pet-list">
            <?php foreach ($mascotas as $pet): ?>
                <li class="pet-info">
                    <strong>Nombre:</strong> <?php echo htmlspecialchars($pet['nombre']); ?><br>
                    <strong>Especie:</strong> <?php echo htmlspecialchars($pet['especie']); ?><br>
                    <strong>Raza:</strong> <?php echo htmlspecialchars($pet['raza']); ?><br>
                    <strong>Edad:</strong> <?php echo htmlspecialchars($pet['edad']); ?>
                    <div class="pet-actions">
                        <button class="edit-button" onclick="openModal('<?php echo $pet['id']; ?>', '<?php echo htmlspecialchars($pet['nombre']); ?>', '<?php echo htmlspecialchars($pet['especie']); ?>', '<?php echo htmlspecialchars($pet['raza']); ?>', '<?php echo htmlspecialchars($pet['edad']); ?>')">Editar</button>
                        <form action="gestion_perfil.php" method="post">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($pet['id']); ?>">
                            <button type="submit" name="delete_pet" class="delete-button">Eliminar</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="client_dashboard.php" class="return-link">Volver a la Página Principal</a>
    </div>

    <!-- Modal para editar mascota -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">×</button>
            <h2>Editar Mascota</h2>
            <form action="gestion_perfil.php" method="post">
                <input type="hidden" id="edit-pet-id" name="id">
                <div class="pet-edit-fields">
                    <label for="edit-nombre">Nombre:</label>
                    <input type="text" id="edit-nombre" name="nombre" required>
                    <label for="edit-especie">Especie:</label>
                    <input type="text" id="edit-especie" name="especie" required>
                    <label for="edit-raza">Raza:</label>
                    <input type="text" id="edit-raza" name="raza" required>
                    <label for="edit-edad">Edad:</label>
                    <input type="text" id="edit-edad" name="edad" required>
                </div>
                <button type="submit" name="edit_pet">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>
