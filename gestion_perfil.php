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
        :root {
            --primary-color: #025162;
            --primary-dark: #03485f;
            --secondary-color: #027a8d;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --bg-primary: #f4f4f9;
            --bg-secondary: #ffffff;
            --text-primary: #333333;
            --text-secondary: #6b7280;
            --border-color: #e0e0e0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f9;
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1.2rem;
            font-weight: 400;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 2rem;
            align-items: stretch;
            flex: 1;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .card {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .profile-card,
        .pets-card {
            height: 100%;
        }

        .card:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }



        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            font-weight: 600;
            margin: 0 auto 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.125rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--bg-primary);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            height: 100%;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .pets-grid::-webkit-scrollbar {
            width: 6px;
        }

        .pets-grid::-webkit-scrollbar-track {
            background: var(--bg-primary);
            border-radius: 3px;
        }

        .pets-grid::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .pets-grid::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .pet-card {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            min-height: 180px;
            display: flex;
            flex-direction: column;
        }

        .pet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .pet-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .pet-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pet-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            font-weight: 600;
        }

        .pet-info h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .pet-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .pet-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .pet-detail {
            text-align: center;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: var(--radius-md);
        }

        .pet-detail-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .pet-detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .pet-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: auto;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-content {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .modal-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.25rem;
            transition: background 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 2rem;
        }

        .return-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-top: 1.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
            font-size: 0.875rem;
            width: fit-content;
        }

        .return-link:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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
        <div class="header">
            <h1>Gestión de Perfil</h1>
            <p>Administra tu información personal y la de tus mascotas</p>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="alert <?php echo strpos($mensaje, 'Error') === false ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo strpos($mensaje, 'Error') === false ? '✅' : '❌'; ?></span>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="main-grid">
            <!-- Perfil del Usuario -->
            <div class="card profile-card">
                <div class="card-header">
                    <h2>Información Personal</h2>
                </div>
                <div class="card-body">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($usuario['nombre_usuario'], 0, 1)); ?>
                    </div>
                    
                    <form action="gestion_perfil.php" method="post">
                        <div class="form-group">
                            <label class="form-label" for="username">Nombre de Usuario</label>
                            <div class="input-group">
                                <span class="input-icon">@</span>
                                <input type="text" id="username" name="username" class="form-input" 
                                       value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-icon">✉</span>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="current_password">Contraseña Actual</label>
                            <div class="input-group">
                                <span class="input-icon">●</span>
                                <input type="password" id="current_password" name="current_password" class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="new_password">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-icon">◐</span>
                                <input type="password" id="new_password" name="new_password" class="form-input">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                            Actualizar Perfil
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mascotas -->
            <div class="card pets-card">
                <div class="card-header">
                    <h2>Registro de Mascotas</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($mascotas)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">◯</div>
                            <h3>No tienes mascotas registradas</h3>
                            <p>Registra tu primera mascota para comenzar</p>
                        </div>
                    <?php else: ?>
                        <div class="pets-grid">
                            <?php foreach ($mascotas as $pet): ?>
                                <div class="pet-card">
                                    <div class="pet-header">
                                        <div class="pet-avatar">
                                            <?php echo strtoupper(substr($pet['nombre'], 0, 1)); ?>
                                        </div>
                                        <div class="pet-info">
                                            <h3><?php echo htmlspecialchars($pet['nombre']); ?></h3>
                                            <p><?php echo htmlspecialchars($pet['especie']); ?> • <?php echo htmlspecialchars($pet['raza']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="pet-details">
                                        <div class="pet-detail">
                                            <div class="pet-detail-label">Especie</div>
                                            <div class="pet-detail-value"><?php echo htmlspecialchars($pet['especie']); ?></div>
                                        </div>
                                        <div class="pet-detail">
                                            <div class="pet-detail-label">Raza</div>
                                            <div class="pet-detail-value"><?php echo htmlspecialchars($pet['raza']); ?></div>
                                        </div>
                                        <div class="pet-detail">
                                            <div class="pet-detail-label">Edad</div>
                                            <div class="pet-detail-value"><?php echo htmlspecialchars($pet['edad']); ?> años</div>
                                        </div>
                                    </div>
                                    
                                    <div class="pet-actions">
                                        <button class="btn btn-secondary" 
                                                onclick="openModal('<?php echo $pet['id']; ?>', '<?php echo htmlspecialchars($pet['nombre']); ?>', '<?php echo htmlspecialchars($pet['especie']); ?>', '<?php echo htmlspecialchars($pet['raza']); ?>', '<?php echo htmlspecialchars($pet['edad']); ?>')"> 
                                            Editar
                                        </button>
                                        <form action="gestion_perfil.php" method="post" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($pet['id']); ?>">
                                            <button type="submit" name="delete_pet" class="btn btn-danger" 
                                                    onclick="return confirm('¿Estás seguro de que quieres eliminar a <?php echo htmlspecialchars($pet['nombre']); ?>?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="client_dashboard.php" class="return-link">
            ← Volver a la Página Principal
        </a>
    </div>

    <!-- Modal para editar mascota -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Información de Mascota</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <form action="gestion_perfil.php" method="post">
                    <input type="hidden" id="edit-pet-id" name="id">
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-nombre">Nombre de la Mascota</label>
                        <div class="input-group">
                            <span class="input-icon">◆</span>
                            <input type="text" id="edit-nombre" name="nombre" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-especie">Especie</label>
                        <div class="input-group">
                            <span class="input-icon">▲</span>
                            <input type="text" id="edit-especie" name="especie" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-raza">Raza</label>
                        <div class="input-group">
                            <span class="input-icon">#</span>
                            <input type="text" id="edit-raza" name="raza" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-edad">Edad (años)</label>
                        <div class="input-group">
                            <span class="input-icon">+</span>
                            <input type="number" id="edit-edad" name="edad" class="form-input" min="0" max="30" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="edit_pet" class="btn btn-primary" style="width: 100%;">
                        Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
