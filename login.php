<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$userEmail = $_POST['userEmail'];
$userPassword = $_POST['userPassword'];

$sql = $conn->prepare("SELECT * FROM user WHERE correo_electronico = ?");
$sql->bind_param("s", $userEmail);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($userPassword, $row['contrasena'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_role'] = $row['rol'];

        // Verificar el rol del usuario y devolver el tipo de usuario adecuado
        if ($row['rol'] == 'admin') {
            echo "admin";
        } else if ($row['rol'] == 'cliente') {
            echo "cliente";
        } else if ($row['rol'] == 'doctor') {
            echo "doctor";
        } else {
            echo "Rol desconocido";
        }
    } else {
        echo "Contraseña incorrecta";
    }
} else {
    echo "No se encontró el usuario";
}

$conn->close();
?>
