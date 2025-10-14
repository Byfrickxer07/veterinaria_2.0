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
    $query = "SELECT nombre_usuario, correo_electronico, telefono FROM user WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug temporal - remover después
    if (!$usuario) {
        echo "Error: No se encontró el usuario con ID: " . $user_id;
        exit;
    }
    
    // Debug temporal - mostrar datos del usuario
    echo "<!-- Debug: Usuario encontrado -->";
    echo "<!-- Debug: Telefono = '" . ($usuario['telefono'] ?? 'NULL') . "' -->";

    // Obtener las mascotas del usuario
    $query = "SELECT * FROM mascotas WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si es una petición de edición de mascota
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pet'])) {
        try {
            $pet_id = $_POST['id'] ?? null;
            $nombre = $_POST['nombre'] ?? '';
            $especie = $_POST['especie'] ?? '';
            $raza = $_POST['raza'] ?? '';
            $edad = $_POST['edad'] ?? '';
            $sexo = $_POST['sexo'] ?? '';
            $peso = $_POST['peso'] ?? '';
            $esterilizado = $_POST['esterilizado'] ?? '';
            
            if (!$pet_id) {
                throw new Exception('ID de mascota no proporcionado');
            }
            
            // Validaciones
            if (empty($nombre) || empty($especie) || empty($raza) || empty($edad) || empty($sexo) || empty($peso) || $esterilizado === '') {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            // Obtener los datos actuales de la mascota para comparar
            $query_check = "SELECT nombre, especie, raza, edad, sexo, peso, esterilizado FROM mascotas WHERE id = :id AND user_id = :user_id";
            $stmt_check = $conn->prepare($query_check);
            $stmt_check->bindParam(':id', $pet_id);
            $stmt_check->bindParam(':user_id', $user_id);
            $stmt_check->execute();
            $current_pet = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_pet) {
                throw new Exception('Mascota no encontrada');
            }
            
            // Verificar si hay cambios
            $has_changes = false;
            if ($current_pet['nombre'] !== $nombre || 
                $current_pet['especie'] !== $especie || 
                $current_pet['raza'] !== $raza || 
                $current_pet['edad'] != $edad || 
                $current_pet['sexo'] !== $sexo || 
                $current_pet['peso'] != $peso || 
                $current_pet['esterilizado'] != $esterilizado) {
                $has_changes = true;
            }
            
            // Verificar si se subió una nueva foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $has_changes = true;
            }
            
            if (!$has_changes) {
                throw new Exception('No se detectaron cambios en la información de la mascota');
            }
            
            if (!is_numeric($edad) || $edad < 0 || $edad > 30) {
                throw new Exception('La edad debe ser un número entre 0 y 30 años');
            }
            
            if (!is_numeric($peso) || $peso <= 0 || $peso > 200) {
                throw new Exception('El peso debe ser un número entre 0.1 y 200 kg');
            }
            
            if (!in_array($sexo, ['Macho', 'Hembra'])) {
                throw new Exception('El sexo debe ser Macho o Hembra');
            }
            
            if (!in_array($esterilizado, ['0', '1'])) {
                throw new Exception('El estado de esterilización debe ser válido');
            }
            
            // Manejar la foto si se subió una nueva
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Formato de archivo no permitido. Use JPG, PNG o GIF');
                }
                
                if ($_FILES['foto']['size'] > 5 * 1024 * 1024) { // 5MB
                    throw new Exception('El archivo es demasiado grande. Máximo 5MB');
                }
                
                $foto_name = 'pet_' . $pet_id . '_' . time() . '.' . $file_extension;
                $foto_path = $upload_dir . $foto_name;
                
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                    throw new Exception('Error al subir la imagen');
                }
            }
            
            // Construir la consulta SQL
            if ($foto_path) {
                $query = "UPDATE mascotas SET nombre = :nombre, especie = :especie, raza = :raza, edad = :edad, 
                         sexo = :sexo, peso = :peso, esterilizado = :esterilizado, foto = :foto 
                         WHERE id = :id AND user_id = :user_id";
            } else {
                $query = "UPDATE mascotas SET nombre = :nombre, especie = :especie, raza = :raza, edad = :edad, 
                         sexo = :sexo, peso = :peso, esterilizado = :esterilizado 
                         WHERE id = :id AND user_id = :user_id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':especie', $especie);
            $stmt->bindParam(':raza', $raza);
            $stmt->bindParam(':edad', $edad);
            $stmt->bindParam(':sexo', $sexo);
            $stmt->bindParam(':peso', $peso);
            $stmt->bindParam(':esterilizado', $esterilizado);
            $stmt->bindParam(':id', $pet_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($foto_path) {
                $stmt->bindParam(':foto', $foto_path);
            }
            
            $stmt->execute();

            // Set success message in session
            $_SESSION['success_message'] = 'La mascota ha sido actualizada correctamente';
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '#mascotas');
            exit();
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    } elseif (isset($_POST['update_profile'])) {
            $nombre_usuario = trim($_POST['username']);
            $correo_electronico = trim($_POST['email']);
            $telefono = preg_replace('/[^0-9]/', '', $_POST['phone']);

            // Validaciones
            $errores = [];

            // Validar nombre de usuario
            if (strlen($nombre_usuario) < 4) {
                $errores[] = "El nombre de usuario debe tener al menos 4 caracteres.";
            } elseif (strlen($nombre_usuario) > 30) {
                $errores[] = "El nombre de usuario no puede tener más de 30 caracteres.";
            }

            // Validar email
            if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
                $errores[] = "Por favor ingresa un correo electrónico válido.";
            } elseif (!preg_match('/@gmail\.com$/', $correo_electronico)) {
                $errores[] = "El correo electrónico debe ser de dominio @gmail.com";
            }

            // Validar teléfono
            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                $errores[] = "El teléfono debe contener exactamente 10 dígitos.";
            }

            if (empty($errores)) {
                try {
                    // Verificar si el correo ya existe
                    $query = "SELECT id FROM user WHERE correo_electronico = :correo AND id != :user_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':correo', $correo_electronico);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $mensaje = "Este correo electrónico ya está en uso por otra cuenta.";
                        echo "<script>
                            Swal.fire({
                                title: 'Error',
                                text: '" . addslashes($mensaje) . "',
                                icon: 'error',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        </script>";
                    } else {
                        $query = "UPDATE user SET nombre_usuario = :nombre_usuario, correo_electronico = :correo_electronico, telefono = :telefono WHERE id = :user_id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':nombre_usuario', $nombre_usuario);
                        $stmt->bindParam(':correo_electronico', $correo_electronico);
                        $stmt->bindParam(':telefono', $telefono);
                        if (!isset($_SESSION['user_id'])) {
                            header("Location: login.php");
                            exit();
                        }
                        $user_id = $_SESSION['user_id'];
                        $stmt->bindParam(':user_id', $user_id);
                        
                        if ($stmt->execute()) {
                            $mensaje = "¡Tu perfil ha sido actualizado con éxito!.";
                            echo "<script>
                                Swal.fire({
                                    title: '¡Éxito!',
                                    text: '" . addslashes($mensaje) . "',
                                    icon: 'success',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            </script>";
                            
                            // Actualizar los datos en la sesión
                            $_SESSION['username'] = $nombre_usuario;
                            $usuario['nombre_usuario'] = $nombre_usuario;
                            $usuario['correo_electronico'] = $correo_electronico;
                            $usuario['telefono'] = $telefono;
                        } else {
                            $mensaje = "Error al actualizar el perfil";
                            echo "<script>
                                Swal.fire({
                                    title: 'Error',
                                    text: '" . addslashes($mensaje) . "',
                                    icon: 'error',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            </script>";
                        }
                    }
                } catch (PDOException $e) {
                    $mensaje = "Error al actualizar el perfil: " . $e->getMessage();
                    echo "<script>
                        Swal.fire({
                            title: 'Error',
                            text: '" . addslashes($mensaje) . "',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    </script>";
                }
            } else {
                echo "<script>
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al actualizar el perfil. Intente nuevamente.',
                        icon: 'error',
                        timer: 3000,
                        showConfirmButton: false
                    });
                </script>";
            }
        } elseif (isset($_POST['delete_pet'])) {
        try {
            $pet_id = $_POST['id'] ?? null;
            
            if (!$pet_id) {
                throw new Exception('ID de mascota no proporcionado');
            }
            
            // Verificar que la mascota pertenece al usuario actual
            $query = "DELETE FROM mascotas WHERE id = :id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $pet_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Mascota eliminada correctamente';
            } else {
                throw new Exception('No se pudo eliminar la mascota');
            }
            
            // Redirigir para evitar reenvío del formulario
            header('Location: ' . $_SERVER['PHP_SELF'] . '#mascotas');
            exit();
            
        } catch (Exception $e) {
            $mensaje = $e->getMessage();
        }
        
    } elseif (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $errores = [];

            // Validar que los campos no estén vacíos
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $errores[] = "Todos los campos son obligatorios.";
            }

            // Validar coincidencia de contraseñas
            if ($new_password !== $confirm_password) {
                $errores[] = "Las contraseñas no coinciden.";
            }

            // Validar fortaleza de la nueva contraseña
            $passwordPattern = '/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';
            if (!preg_match($passwordPattern, $new_password)) {
                $errores[] = "La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.";
            }

            if (empty($errores)) {
                try {
                    // Obtener la contraseña actual del usuario
                    $query = "SELECT id, contrasena FROM user WHERE id = :user_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$usuario) {
                        throw new Exception("Usuario no encontrado");
                    }

                    // Verificar que la contraseña actual sea correcta
                    if (!password_verify($current_password, $usuario['contrasena'])) {
                        $errores[] = "La contraseña actual es incorrecta.";
                    } 
                    // Verificar que la nueva contraseña sea diferente a la actual
                    else if (password_verify($new_password, $usuario['contrasena'])) {
                        $errores[] = "La nueva contraseña no puede ser igual a la actual.";
                    } else {
                        // Actualizar la contraseña
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $query = "UPDATE user SET contrasena = :contrasena WHERE id = :user_id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':contrasena', $hashed_password);
                        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        
                        if ($stmt->execute()) {
                            // Guardar mensaje de éxito en sesión
                            $_SESSION['success_message'] = 'Tu contraseña ha sido actualizada correctamente';
                            // Redirigir para evitar reenvío del formulario
                            header('Location: ' . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            throw new Exception("Error al actualizar la contraseña en la base de datos");
                        }
                    }
                } catch (Exception $e) {
                    $errores[] = $e->getMessage();
                }
            }

            if (!empty($errores)) {
                // Guardar errores en sesión
                $_SESSION['error_messages'] = $errores;
                // Redirigir para evitar reenvío del formulario
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }
 catch (PDOException $e) {
    $mensaje = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Perfil</title>
    <!-- SweetAlert2 CSS y JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verificar si SweetAlert2 está cargado
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 no se cargó correctamente');
            // Cargar SweetAlert2 de nuevo si falla la primera vez
            document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>');
        }
    </script>
    
    <script>
        // Mostrar mensajes de PHP con SweetAlert2
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($mensaje)): ?>
                Swal.fire({
                    title: '<?php echo strpos($mensaje, 'éxito') !== false ? "¡Éxito!" : "Error"; ?>',
                    text: '<?php echo addslashes($mensaje); ?>',
                    icon: '<?php echo strpos($mensaje, 'éxito') !== false ? "success" : "error"; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>

            // Configurar la validación del formulario de perfil
            const profileForm = document.querySelector('.profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const email = document.getElementById('email').value.trim();
                    
                    if (!username || !email) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'Por favor completa todos los campos requeridos',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'Por favor ingresa un correo electrónico válido',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    return true;
                });
            }
        });
    </script>
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
            position: relative;
            text-align: center;
            margin: 2rem 0;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        
        .return-link {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .return-link:hover {
            color: var(--primary-dark);
        }
        
        .header-content {
            text-align: center;
            width: 100%;
        }
        
        .header h1 {
            margin: 0;
            color: var(--primary-color);
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
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            justify-content: center;
            flex: 1;
            white-space: nowrap;
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
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            max-height: 420px; /* Altura para mostrar exactamente 2 mascotas completas */
            overflow-y: auto;
            padding-right: 0.5rem;
            align-content: flex-start;
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
            min-height: 200px;
            width: calc(50% - 0.5rem); /* 2 columnas en lugar de 3 */
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            min-width: 280px;
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
            margin: 0.25rem 0;
        }
        
        .pet-age-badge {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            display: inline-block;
            margin-top: 0.25rem;
            border: 1px solid #bae6fd;
        }

        .pet-details {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .pet-detail {
            text-align: center;
            padding: 0.5rem 0.75rem;
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            flex: 1;
            min-width: calc(50% - 0.5rem);
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
            gap: 0.5rem;
            justify-content: space-between;
            margin-top: auto;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            transform: translateY(20px);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
            opacity: 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            color: white;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-header {
            padding: 1.75rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 16px 16px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .modal-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: var(--text-secondary);
            z-index: 2;
        }

        /* Eliminar flechas de los campos de número */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }

        .form-input {
            padding-left: 3rem !important;
            padding-right: 3.5rem !important;
            width: 100%;
            height: 48px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* Eliminar flecha del select */
        select.form-input {
            background-image: none !important;
            padding-right: 1rem !important;
        }

        .toggle-password {
            position: absolute;
            right: 0.5rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            font-size: 1.1rem;
            line-height: 1;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .toggle-password:hover {
            color: #025162;
            background-color: rgba(0, 0, 0, 0.05);
        }

        .toggle-password:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(2, 81, 98, 0.1);
        }
        
        .form-input:focus, select.form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(2, 81, 98, 0.1);
            background-color: white;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 81, 98, 0.2);
        }

        .return-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            background-color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: var(--primary-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
            font-size: 0.875rem;
            width: fit-content;
        }

        .return-link:hover {
            background: var(--primary-color);
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

        /* Responsive para mascotas */
        @media (max-width: 768px) {
            .pet-card {
                width: 100%; /* Una columna en móviles */
                min-width: auto;
            }
            
            .pets-grid {
                max-height: 400px; /* Altura para mostrar 2 mascotas en móviles */
            }
        }
    </style>
    <script>
        // Datos de razas por especie
        const razasPorEspecie = {
            'Perro': ['Labrador Retriever', 'Pastor Alemán', 'Bulldog', 'Golden Retriever', 'Poodle', 'Beagle', 'Chihuahua', 'Boxer', 'Dálmata', 'Husky Siberiano', 'Sin raza definida'],
            'Gato': ['Siamés', 'Persa', 'Maine Coon', 'Bengalí', 'Esfinge', 'Azul Ruso', 'Angora Turco', 'Ragdoll', 'British Shorthair', 'Siberiano', 'Sin raza definida'],
            'Conejo': ['Holandés Enano', 'Cabeza de León', 'Angora', 'Rex', 'Belier', 'Gigante de Flandes', 'Mini Lop', 'Conejo Enano', 'Sin raza definida'],
            'Ave': ['Periquito', 'Canario', 'Cacatúa', 'Agapornis', 'Loro', 'Ninfa', 'Diamante Mandarín', 'Jilguero', 'Sin raza definida'],
            'Roedor': ['Hámster Sirio', 'Cobaya', 'Conejillo de Indias', 'Ratón Doméstico', 'Rata Doméstica', 'Jerbo', 'Chinchilla', 'Degú', 'Sin raza definida']
        };

        function actualizarRazas(especieSeleccionada) {
            const razaSelect = document.getElementById('edit-raza');
            // Limpiar opciones actuales
            razaSelect.innerHTML = '';
            
            // Agregar opción predeterminada
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.disabled = true;
            defaultOption.selected = true;
            defaultOption.textContent = 'Seleccione una raza...';
            razaSelect.appendChild(defaultOption);
            
            // Agregar razas correspondientes
            if (especieSeleccionada && razasPorEspecie[especieSeleccionada]) {
                razasPorEspecie[especieSeleccionada].forEach(raza => {
                    const option = document.createElement('option');
                    option.value = raza;
                    option.textContent = raza;
                    razaSelect.appendChild(option);
                });
            }
        }

        function openModal(id, nombre, especie, raza, edad, sexo, peso, esterilizado, foto) {
            // Set the pet ID
            document.getElementById('edit-pet-id').value = id;
            
            // Set the pet name
            const nombreInput = document.getElementById('edit-nombre');
            nombreInput.value = nombre;
            nombreInput.dataset.original = nombre; // Store original value
            
            // Set the species and update the breeds
            const especieSelect = document.getElementById('edit-especie');
            especieSelect.value = especie;
            especieSelect.dataset.original = especie; // Store original value
            actualizarRazas(especie);
            
            // Set the breed after the options are loaded
            setTimeout(() => {
                const razaSelect = document.getElementById('edit-raza');
                razaSelect.value = raza;
                razaSelect.dataset.original = raza; // Store original value
            }, 100); // Increased timeout to ensure options are loaded
            
            // Set the age
            const edadInput = document.getElementById('edit-edad');
            edadInput.value = edad;
            edadInput.dataset.original = edad; // Store original value
            
            // Set the sex
            const sexoSelect = document.getElementById('edit-sexo');
            sexoSelect.value = sexo;
            sexoSelect.dataset.original = sexo; // Store original value
            
            // Set the weight
            const pesoInput = document.getElementById('edit-peso');
            pesoInput.value = parseFloat(peso);
            pesoInput.dataset.original = peso; // Store original value
            
            // Set the sterilization status
            const esterilizadoSelect = document.getElementById('edit-esterilizado');
            esterilizadoSelect.value = esterilizado;
            esterilizadoSelect.dataset.original = esterilizado; // Store original value
            
            // Set the photo preview if exists
            const fotoPreview = document.getElementById('foto-preview');
            const fotoInput = document.getElementById('edit-foto');
            if (foto && foto !== 'null' && foto !== '') {
                fotoPreview.src = foto;
                fotoPreview.style.display = 'block';
            } else {
                fotoPreview.style.display = 'none';
            }
            fotoInput.value = ''; // Clear the file input
            
            // Show the modal with animation
            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
            // Force reflow for animation
            void modal.offsetWidth;
            modal.classList.add('active');
            
            // Focus on the first input field for better UX
            document.getElementById('edit-nombre').focus();
        }

        function closeModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('active');
            // Esperar a que termine la animación antes de ocultar
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    </script>
    <script>
        // Funciones para el modal

        function closeModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('active');
            // Wait for the animation to complete before hiding
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Función para confirmar eliminación de mascota con SweetAlert2
        function confirmDeletePet(petId, petName) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Estás seguro de que quieres eliminar a ${petName}? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Crear formulario dinámico para enviar la eliminación
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'gestion_perfil.php';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = petId;
                    
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_pet';
                    deleteInput.value = '1';
                    
                    form.appendChild(idInput);
                    form.appendChild(deleteInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Funcionalidad para mostrar/ocultar contraseñas
        // Función para validar el formulario de contraseña
        function validatePasswordForm() {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorDiv = document.getElementById('passwordErrors');
            
            // Limpiar mensajes de error previos
            errorDiv.style.display = 'none';
            errorDiv.innerHTML = '';
            
            // Validar que todos los campos estén completos
            if (!currentPassword || !newPassword || !confirmPassword) {
                errorDiv.innerHTML = 'Todos los campos son obligatorios';
                errorDiv.style.display = 'block';
                return false;
            }
            
            // Validar que las contraseñas coincidan
            if (newPassword !== confirmPassword) {
                errorDiv.innerHTML = 'Las contraseñas no coinciden';
                errorDiv.style.display = 'block';
                return false;
            }

            // Validar fortaleza de la contraseña (mismos requisitos que en el registro)
            const passwordPattern = /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;
            if (!passwordPattern.test(newPassword)) {
                errorDiv.innerHTML = 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número';
                errorDiv.style.display = 'block';
                return false;
            }

            // Si todo está bien, permitir el envío del formulario
            return true;
        }

        // Función para mostrar alerta de éxito
        function showSuccessAlert(message) {
            return Swal.fire({
                title: '¡Éxito!',
                text: message,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Función para mostrar alerta de error
        function showErrorAlert(message) {
            return Swal.fire({
                title: 'Error',
                html: message,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }

        // Manejar el envío del formulario de contraseña
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const formData = new FormData(this);
                
                // Mostrar indicador de carga
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Actualizando...';
                }
                
                try {
                    const response = await fetch('gestion_perfil.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Mostrar mensaje de éxito
                        await Swal.fire({
                            title: '¡Éxito!',
                            text: result.message,
                            icon: 'success',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        
                        // Limpiar y ocultar el formulario
                        this.reset();
                        const passwordSection = document.getElementById('passwordSection');
                        const showBtn = document.getElementById('showPasswordForm');
                        
                        if (passwordSection) passwordSection.style.display = 'none';
                        if (showBtn) showBtn.style.display = 'block';
                        
                    } else if (result.errors && result.errors.length > 0) {
                        // Mostrar errores
                        await Swal.fire({
                            title: 'Error',
                            html: result.errors.join('<br>'),
                            icon: 'error',
                            showConfirmButton: true
                        });
                    } else {
                        throw new Error('Respuesta inesperada del servidor');
                    }
                    
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al procesar la solicitud',
                        icon: 'error',
                        showConfirmButton: true
                    });
                } finally {
                    // Restaurar el botón
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Actualizar Contraseña';
                    }
                }
            });
        }

        // Mostrar/ocultar contraseña
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                const button = input.nextElementSibling;
                if (button && button.classList.contains('toggle-password')) {
                    button.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar formulario de contraseña
            const showPasswordForm = document.getElementById('showPasswordForm');
            const hidePasswordForm = document.getElementById('hidePasswordForm');
            const passwordSection = document.getElementById('passwordSection');

            // Mostrar formulario de contraseña
            if (showPasswordForm) {
                showPasswordForm.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (passwordSection) {
                        passwordSection.style.display = 'block';
                        showPasswordForm.style.display = 'none';
                    }
                });
            }

            // Ocultar formulario de contraseña
            if (hidePasswordForm && passwordSection && showPasswordForm) {
                hidePasswordForm.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Limpiar los campos de contraseña
                    const currentPassword = document.getElementById('current_password');
                    const newPassword = document.getElementById('new_password');
                    const confirmPassword = document.getElementById('confirm_password');
                    const passwordMatch = document.getElementById('passwordMatch');
                    
                    if (currentPassword) currentPassword.value = '';
                    if (newPassword) newPassword.value = '';
                    if (confirmPassword) confirmPassword.value = '';
                    if (passwordMatch) passwordMatch.textContent = '';
                    
                    // Ocultar la sección
                    passwordSection.style.display = 'none';
                    // Mostrar el botón de cambiar contraseña
                    showPasswordForm.style.display = 'block';
                });
            }

            // Configurar botones para mostrar/ocultar contraseña
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    togglePasswordVisibility(targetId);
                    // Mantener el foco en el input
                    input.focus();
                });
            });

            // Validación de coincidencia de contraseñas
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');

            function validatePassword() {
                if (!newPassword.value || !confirmPassword.value) {
                    passwordMatch.textContent = '';
                    return false;
                }
                
                if (newPassword.value !== confirmPassword.value) {
                    passwordMatch.textContent = '✗ Las contraseñas no coinciden';
                    passwordMatch.style.color = '#ef4444';
                    return false;
                } else {
                    passwordMatch.textContent = '✓ Las contraseñas coinciden';
                    passwordMatch.style.color = '#10B981';
                    return true;
                }
            }

            if (newPassword && confirmPassword) {
                newPassword.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);

                // Validar el formulario antes de enviar
                const passwordForm = document.getElementById('passwordForm');
                if (passwordForm) {
                    passwordForm.addEventListener('submit', function(e) {
                        if (!validatePassword()) {
                            e.preventDefault();
                            return false;
                        }
                        return true;
                    });
                }
            }

            // Validación del formulario de perfil
            const profileForm = document.querySelector('.profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const email = document.getElementById('email').value.trim();
                    
                    if (!username || !email) {
                        e.preventDefault();
                        alert('Por favor complete todos los campos requeridos');
                        return false;
                    }
                    
                    // Validar formato de correo electrónico
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        alert('Por favor ingrese un correo electrónico válido');
                        return false;
                    }
                    
                    return true;
                });
            }
        });
    </script>
</head>
<body>
    <?php 
    // Mostrar mensajes de éxito
    if (isset($_SESSION['success_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '¡Éxito!',
                    text: '" . addslashes($_SESSION['success_message']) . "',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>";
        // Eliminar el mensaje después de mostrarlo
        unset($_SESSION['success_message']);
    }
    
    // Mostrar mensajes de error
    if (isset($_SESSION['error_messages']) && is_array($_SESSION['error_messages'])) {
        $errorMessage = implode("\\n", $_SESSION['error_messages']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error',
                    text: '" . addslashes($errorMessage) . "',
                    icon: 'error',
                    timer: 5000,
                    showConfirmButton: true
                });
            });
        </script>";
        // Eliminar los mensajes de error después de mostrarlos
        unset($_SESSION['error_messages']);
    }
    ?>
    <div class="container">
        <div class="header">
            <a href="client_dashboard.php" class="return-link">
                <i class='bx bx-arrow-back'></i> Volver al Inicio
            </a>
            <div class="header-content">
                <h1>Gestión de Perfil</h1>
                <p>Administra tu información personal y la de tus mascotas</p>
            </div>
        </div>

        <div class="main-grid">
            <!-- Sección de Perfil -->
            <div class="card profile-card">
                <div class="card-header">
                    <h2>Información del Perfil</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label class="form-label" for="username">Nombre de Usuario</label>
                            <div class="input-group">
                                <span class="input-icon">👤</span>
                                <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-icon">✉️</span>
                                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($usuario['correo_electronico']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Teléfono (solo números)</label>
                            <div class="input-group">
                                <span class="input-icon"><i class='bx bx-phone'></i></span>
                                <input type="tel" id="phone" name="phone" class="form-input" 
                                       pattern="[0-9]{10}" 
                                       title="Por favor ingresa exactamente 10 números"
                                       maxlength="10"
                                       value="<?php echo isset($usuario['telefono']) && !empty($usuario['telefono']) ? htmlspecialchars($usuario['telefono']) : ''; ?>" required>
                            </div>
                            <small class="form-text text-muted">Ejemplo: 1122334455</small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                            Actualizar Información
                        </button>

                        <button type="button" id="showPasswordForm" class="btn btn-outline" style="width: 100%; margin-top: 1rem;">
                            Cambiar Contraseña
                        </button>
                        
                        <script>
                        // Script directo para manejar el botón de cambio de contraseña
                        document.getElementById('showPasswordForm').addEventListener('click', function() {
                            const passwordSection = document.getElementById('passwordSection');
                            const showPasswordBtn = document.getElementById('showPasswordForm');
                            
                            if (passwordSection) {
                                passwordSection.style.display = 'block';
                                showPasswordBtn.style.display = 'none';
                            }
                        });
                        
                        // Manejar el botón de cancelar
                        const hidePasswordForm = document.getElementById('hidePasswordForm');
                        if (hidePasswordForm) {
                            hidePasswordForm.addEventListener('click', function(e) {
                                e.preventDefault();
                                const passwordSection = document.getElementById('passwordSection');
                                const showPasswordBtn = document.getElementById('showPasswordForm');
                                
                                if (passwordSection && showPasswordBtn) {
                                    passwordSection.style.display = 'none';
                                    showPasswordBtn.style.display = 'block';
                                    
                                    // Limpiar campos
                                    document.getElementById('current_password').value = '';
                                    document.getElementById('new_password').value = '';
                                    document.getElementById('confirm_password').value = '';
                                }
                            });
                        }
                        </script>
                    </form>
                    
                    <!-- Formulario de Cambio de Contraseña (inicialmente oculto) -->
                    <div id="passwordSection" class="password-section" style="display: none; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                        <h3>Cambiar Contraseña</h3>
                        <form id="passwordForm" method="POST" action="gestion_perfil.php">
                            <input type="hidden" name="update_password" value="1">
                            <div class="form-group">
                                <label class="form-label" for="current_password">Contraseña Actual</label>
                                <div class="input-group" style="position: relative;">
                                    <input type="password" id="current_password" name="current_password" class="form-input" required
                                           value="<?php echo isset($_POST['current_password']) ? htmlspecialchars($_POST['current_password']) : ''; ?>">
                                    <i class='bx bx-show' id="toggle-current-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a7a7a7; z-index: 10;"></i>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="new_password">Nueva Contraseña</label>
                                <div class="input-group" style="position: relative;">
                                    <input type="password" id="new_password" name="new_password" class="form-input" required
                                           value="<?php echo isset($_POST['new_password']) ? htmlspecialchars($_POST['new_password']) : ''; ?>">
                                    <i class='bx bx-show' id="toggle-new-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a7a7a7; z-index: 10;"></i>
                                </div>
                                <small class="form-hint">Mínimo 8 caracteres, incluyendo al menos una mayúscula y un número</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirmar Nueva Contraseña</label>
                                <div class="input-group" style="position: relative;">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required
                                           value="<?php echo isset($_POST['confirm_password']) ? htmlspecialchars($_POST['confirm_password']) : ''; ?>">
                                    <i class='bx bx-show' id="toggle-confirm-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a7a7a7; z-index: 10;"></i>
                                </div>
                                <div id="passwordMatch" class="form-hint"></div>
                            </div>

                            <script>
                            // Validación en tiempo real de coincidencia de contraseñas
                            document.addEventListener('DOMContentLoaded', function() {
                                const newPassword = document.getElementById('new_password');
                                const confirmPassword = document.getElementById('confirm_password');
                                const passwordMatch = document.getElementById('passwordMatch');

                                function checkPasswordMatch() {
                                    if (newPassword.value && confirmPassword.value) {
                                        if (newPassword.value === confirmPassword.value) {
                                            passwordMatch.textContent = 'Las contraseñas coinciden';
                                            passwordMatch.style.color = 'green';
                                        } else {
                                            passwordMatch.textContent = 'Las contraseñas no coinciden';
                                            passwordMatch.style.color = 'red';
                                        }
                                    } else {
                                        passwordMatch.textContent = '';
                                    }
                                }

                                newPassword.addEventListener('input', checkPasswordMatch);
                                confirmPassword.addEventListener('input', checkPasswordMatch);

                                // Funcionalidad para mostrar/ocultar contraseñas
                                function setupPasswordToggle(toggleId, inputId) {
                                    const toggle = document.getElementById(toggleId);
                                    const input = document.getElementById(inputId);
                                    
                                    if (toggle && input) {
                                        toggle.addEventListener('click', function() {
                                            if (input.type === 'password') {
                                                input.type = 'text';
                                                toggle.classList.remove('bx-show');
                                                toggle.classList.add('bx-hide');
                                            } else {
                                                input.type = 'password';
                                                toggle.classList.remove('bx-hide');
                                                toggle.classList.add('bx-show');
                                            }
                                        });
                                    }
                                }

                                // Configurar toggles para cada campo de contraseña
                                setupPasswordToggle('toggle-current-password', 'current_password');
                                setupPasswordToggle('toggle-new-password', 'new_password');
                                setupPasswordToggle('toggle-confirm-password', 'confirm_password');

                                // Validar antes de enviar el formulario
                                const form = document.getElementById('passwordForm');
                                if (form) {
                                    form.addEventListener('submit', function(e) {
                                        if (newPassword.value !== confirmPassword.value) {
                                            e.preventDefault();
                                            Swal.fire({
                                                title: 'Error',
                                                text: 'Las contraseñas no coinciden',
                                                icon: 'error',
                                                timer: 3000,
                                                showConfirmButton: true
                                            });
                                            return false;
                                        }
                                        return true;
                                    });
                                }
                            });
                            </script>
                            
                            <div class="form-actions" style="display: flex; gap: 1rem; margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    Actualizar Contraseña
                                </button>
                                <button type="button" id="hidePasswordForm" class="btn btn-outline" style="flex: 1;">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mascotas -->
            <div class="card pets-card">
                <div class="card-header">
                    <h2>Registro de Mascotas</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($mascotas)): ?>
                        <div class="no-pets" style="text-align: center; padding: 2rem;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">🐾</div>
                            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">No tienes mascotas registradas</h3>
                            <p style="color: var(--text-tertiary); margin-bottom: 1.5rem;">¡Agrega tu primera mascota para comenzar!</p>
                            <!-- Aquí podrían ir botones de acción si se desean, pero no links de navegación del sidebar -->
                        <?php else: ?>
                        <div class="pets-grid">
                            <?php foreach ($mascotas as $pet): ?>
                                <div class="pet-card">
                                    <div class="pet-header">
                                        <div class="pet-avatar">
                                            <?php 
                                            // Debug temporal
                                            echo "<!-- Debug: Foto = '" . ($pet['foto'] ?? 'NULL') . "' -->";
                                            echo "<!-- Debug: File exists = " . (file_exists($pet['foto'] ?? '') ? 'YES' : 'NO') . " -->";
                                            echo "<!-- Debug: Empty check = " . (empty($pet['foto']) ? 'EMPTY' : 'NOT EMPTY') . " -->";
                                            if (!empty($pet['foto']) && file_exists($pet['foto'])): ?>
                                                <img src="<?php echo htmlspecialchars($pet['foto']); ?>" alt="<?php echo htmlspecialchars($pet['nombre']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($pet['nombre'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pet-info">
                                            <h3><?php echo htmlspecialchars($pet['nombre']); ?></h3>
                                            <p><?php echo htmlspecialchars($pet['especie']); ?> • <?php echo htmlspecialchars($pet['raza']); ?></p>
                                            <div class="pet-age-badge">
                                                <?php echo htmlspecialchars($pet['edad']); ?> años
                                            </div>
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
                                        <div class="pet-detail">
                                            <div class="pet-detail-label">Sexo</div>
                                            <div class="pet-detail-value"><?php echo htmlspecialchars($pet['sexo']); ?></div>
                                        </div>
                                        <div class="pet-detail">
                                            <div class="pet-detail-label">Peso</div>
                                            <div class="pet-detail-value"><?php echo htmlspecialchars($pet['peso']); ?> kg</div>
                                        </div>
                                        <div class="pet-detail">
                                            <div class="pet-detail-label">Esterilizado</div>
                                            <div class="pet-detail-value"><?php echo $pet['esterilizado'] ? 'Sí' : 'No'; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="pet-actions">
                                        <button class="btn btn-secondary" 
                                                onclick="openModal('<?php echo $pet['id']; ?>', '<?php echo htmlspecialchars($pet['nombre']); ?>', '<?php echo htmlspecialchars($pet['especie']); ?>', '<?php echo htmlspecialchars($pet['raza']); ?>', '<?php echo htmlspecialchars($pet['edad']); ?>', '<?php echo htmlspecialchars($pet['sexo']); ?>', '<?php echo htmlspecialchars($pet['peso']); ?>', '<?php echo htmlspecialchars($pet['esterilizado']); ?>', '<?php echo htmlspecialchars($pet['foto']); ?>')"> 
                                            Editar
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="confirmDeletePet('<?php echo $pet['id']; ?>', '<?php echo htmlspecialchars($pet['nombre']); ?>')">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal para editar mascota -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Información de Mascota</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <form action="gestion_perfil.php" method="post" id="editPetForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit-pet-id" name="id">
                    <input type="hidden" name="edit_pet" value="1">
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-nombre"><i class='bx bx-user' style="margin-right: 5px;"></i>Nombre de la Mascota</label>
                        <div class="input-group">
                            <input type="text" id="edit-nombre" name="nombre" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-especie"><i class='bx bx-category' style="margin-right: 5px;"></i>Especie</label>
                        <div class="input-group">
                            <select id="edit-especie" name="especie" class="form-input" required onchange="actualizarRazas(this.value)">
                                <option value="" disabled selected>Seleccionar especie...</option>
                                <option value="Perro">Perro</option>
                                <option value="Gato">Gato</option>
                                <option value="Conejo">Conejo</option>
                                <option value="Ave">Ave</option>
                                <option value="Roedor">Roedor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-raza"><i class='bx bx-dna' style="margin-right: 5px;"></i>Raza</label>
                        <div class="input-group">
                            <select id="edit-raza" name="raza" class="form-input" required>
                                <option value="" disabled selected>Seleccione una especie primero</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-edad"><i class='bx bx-time' style="margin-right: 5px;"></i>Edad (años)</label>
                        <div class="input-group">
                            <input type="number" id="edit-edad" name="edad" class="form-input" min="0" max="30" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-sexo"><i class='bx bx-male-sign' style="margin-right: 5px;"></i>Sexo</label>
                        <div class="input-group">
                            <select id="edit-sexo" name="sexo" class="form-input" required>
                                <option value="" disabled>Seleccionar sexo...</option>
                                <option value="Macho">Macho</option>
                                <option value="Hembra">Hembra</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-peso"><i class='bx bx-chart' style="margin-right: 5px;"></i>Peso (kg)</label>
                        <div class="input-group">
                            <input type="number" id="edit-peso" name="peso" class="form-input" min="0.1" max="200" step="0.1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-esterilizado"><i class='bx bx-check-shield' style="margin-right: 5px;"></i>Estado de Esterilización</label>
                        <div class="input-group">
                            <select id="edit-esterilizado" name="esterilizado" class="form-input" required>
                                <option value="" disabled>Seleccionar estado...</option>
                                <option value="1">Esterilizado</option>
                                <option value="0">No esterilizado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="edit-foto"><i class='bx bx-image' style="margin-right: 5px;"></i>Foto de la Mascota</label>
                        <div class="input-group">
                            <input type="file" id="edit-foto" name="foto" class="form-input" accept="image/*">
                        </div>
                        <small class="form-hint">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                        
                        <!-- Preview de la imagen actual -->
                        <div id="current-photo-preview" style="margin-top: 10px;">
                            <img id="foto-preview" src="" alt="Foto actual" style="max-width: 150px; max-height: 150px; border-radius: 8px; display: none;">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Guardar Cambios
                    </button>
                </form>

                <script>
                // Mostrar mensaje de éxito si existe en la sesión
                <?php if (isset($_SESSION['success_message'])): ?>
                    Swal.fire({
                        title: '¡Éxito!',
                        text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                // Validación del formulario de edición de mascota
                document.getElementById('editPetForm').addEventListener('submit', function(e) {
                    const nombre = document.getElementById('edit-nombre').value.trim();
                    const especie = document.getElementById('edit-especie').value;
                    const raza = document.getElementById('edit-raza').value;
                    const edad = document.getElementById('edit-edad').value;
                    const sexo = document.getElementById('edit-sexo').value;
                    const peso = document.getElementById('edit-peso').value;
                    const esterilizado = document.getElementById('edit-esterilizado').value;
                    const foto = document.getElementById('edit-foto').files[0];
                    
                    // Validar campos obligatorios
                    if (!nombre || !especie || !raza || !edad || !sexo || !peso || esterilizado === '') {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'Todos los campos son obligatorios',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    // Verificar si hay cambios (datos originales se almacenan en data attributes)
                    const originalData = {
                        nombre: document.getElementById('edit-nombre').dataset.original || '',
                        especie: document.getElementById('edit-especie').dataset.original || '',
                        raza: document.getElementById('edit-raza').dataset.original || '',
                        edad: document.getElementById('edit-edad').dataset.original || '',
                        sexo: document.getElementById('edit-sexo').dataset.original || '',
                        peso: document.getElementById('edit-peso').dataset.original || '',
                        esterilizado: document.getElementById('edit-esterilizado').dataset.original || ''
                    };
                    
                    const hasChanges = (
                        originalData.nombre !== nombre ||
                        originalData.especie !== especie ||
                        originalData.raza !== raza ||
                        originalData.edad !== edad ||
                        originalData.sexo !== sexo ||
                        originalData.peso !== peso ||
                        originalData.esterilizado !== esterilizado ||
                        foto // Si hay una nueva foto
                    );
                    
                    if (!hasChanges) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Sin cambios',
                            text: 'No se detectaron cambios en la información de la mascota',
                            icon: 'info',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    // Validar edad
                    if (isNaN(edad) || edad < 0 || edad > 30) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'La edad debe ser un número entre 0 y 30 años',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    // Validar peso
                    if (isNaN(peso) || peso <= 0 || peso > 200) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'El peso debe ser un número entre 0.1 y 200 kg',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    // Validar archivo de imagen si se seleccionó uno
                    if (foto) {
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(foto.type)) {
                            e.preventDefault();
                            Swal.fire({
                                title: 'Error',
                                text: 'Formato de archivo no permitido. Use JPG, PNG o GIF',
                                icon: 'error',
                                timer: 3000,
                                showConfirmButton: false
                            });
                            return false;
                        }
                        
                        if (foto.size > 5 * 1024 * 1024) { // 5MB
                            e.preventDefault();
                            Swal.fire({
                                title: 'Error',
                                text: 'El archivo es demasiado grande. Máximo 5MB',
                                icon: 'error',
                                timer: 3000,
                                showConfirmButton: false
                            });
                            return false;
                        }
                    }
                    
                    return true;
                });
                
                // Preview de imagen cuando se selecciona un archivo
                document.getElementById('edit-foto').addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('foto-preview');
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
                </script>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Mostrar mensajes de PHP con SweetAlert2
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($mensaje)): ?>
                Swal.fire({
                    title: '<?php echo strpos($mensaje, 'éxito') !== false ? "¡Éxito!" : "Error"; ?>',
                    text: '<?php echo addslashes($mensaje); ?>',
                    icon: '<?php echo strpos($mensaje, 'éxito') !== false ? "success" : "error"; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>

            // Configurar la validación del formulario de perfil
            const profileForm = document.querySelector('.profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const email = document.getElementById('email').value.trim();
                    
                    if (!username || !email) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'Por favor completa todos los campos requeridos',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Error',
                            text: 'Por favor ingresa un correo electrónico válido',
                            icon: 'error',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return false;
                    }
                    
                    return true;
                });
            }

            // Configurar validación del teléfono
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                // Solo permitir números y máximo 10 dígitos
                phoneInput.addEventListener('input', function(e) {
                    // Remover cualquier carácter que no sea número
                    let value = e.target.value.replace(/[^0-9]/g, '');
                    
                    // Limitar a máximo 10 dígitos
                    if (value.length > 10) {
                        value = value.substring(0, 10);
                    }
                    
                    e.target.value = value;
                });
                
                // Prevenir pegar texto que no sean números
                phoneInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numbers = paste.replace(/[^0-9]/g, '').substring(0, 10);
                    e.target.value = numbers;
                });
            }
        });
    </script>
    <!-- Iframe oculto para el envío del formulario de contraseña -->
    <iframe name="hiddenIframe" id="hiddenIframe" style="display: none;"></iframe>
</body>
</html>
