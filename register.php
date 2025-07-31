<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos del formulario
$userName = $_POST['userName'];
$userEmail = $_POST['userEmail'];
$userPassword = $_POST['userPassword'];
$userPhone = $_POST['userPhone'];
$userDNI = $_POST['userDNI'];
$userLastName = $_POST['userLastName'];

// Validaciones
if (strlen($userName) < 4) {
    echo "El nombre de usuario debe tener más de 4 caracteres.";
    exit;
}

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Correo electrónico no válido.";
    exit;
}

if (strlen($userPassword) < 8 || !preg_match('/[A-Z]/', $userPassword) || !preg_match('/\d/', $userPassword)) {
    echo "La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.";
    exit;
}

// Verificar si el correo electrónico o nombre de usuario ya están en uso
$sql = "SELECT * FROM user WHERE correo_electronico='$userEmail' OR nombre_usuario='$userName'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "El nombre de usuario o el correo electrónico ya están en uso.";
    exit;
}

// Encriptar la contraseña
$hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

// Insertar el nuevo usuario
$sql = "INSERT INTO user (nombre_usuario, correo_electronico, contrasena, telefono, dni, apellido, rol) 
        VALUES ('$userName', '$userEmail', '$hashedPassword', '$userPhone', '$userDNI', '$userLastName', 'cliente')";

if ($conn->query($sql) === TRUE) {
    echo "Registro exitoso";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Cerrar conexión
$conn->close();
?>
