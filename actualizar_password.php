<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo "No autorizado";
    exit();
}

$user_id = $_SESSION['user_id'];

header('Content-Type: text/html; charset=utf-8');

try {
    $conn = new PDO("mysql:host=localhost;dbname=veterinaria", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        // Obtener la contraseña actual del usuario
        $query = "SELECT contrasena FROM user WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
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
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                echo "
                <script>
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Tu contraseña ha sido actualizada correctamente',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                </script>
                ";
                exit();
            } else {
                throw new Exception("Error al actualizar la contraseña en la base de datos");
            }
        }
    }

    if (!empty($errores)) {
        $errorMessage = implode("\n", array_map('addslashes', $errores));
        echo "
        <script>
            Swal.fire({
                title: 'Error',
                text: '" . $errorMessage . "',
                icon: 'error',
                timer: 3000,
                showConfirmButton: true
            });
        </script>
        ";
    }

} catch (Exception $e) {
    echo "
    <script>
        Swal.fire({
            title: 'Error',
            text: '" . addslashes($e->getMessage()) . "',
            icon: 'error',
            timer: 3000,
            showConfirmButton: true
        });
    </script>
    ";
}
?>
