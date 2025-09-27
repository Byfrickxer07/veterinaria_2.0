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

// Obtener y normalizar datos del formulario
$userName = isset($_POST['userName']) ? trim($_POST['userName']) : '';
$userEmail = isset($_POST['userEmail']) ? trim($_POST['userEmail']) : '';
$userPassword = isset($_POST['userPassword']) ? $_POST['userPassword'] : '';
$userPhone = isset($_POST['userPhone']) ? trim($_POST['userPhone']) : '';
$userDNI = isset($_POST['userDNI']) ? trim($_POST['userDNI']) : '';
$userLastName = isset($_POST['userLastName']) ? trim($_POST['userLastName']) : '';

// Validaciones
if (strlen($userName) < 4) {
    echo "El nombre de usuario debe tener más de 4 caracteres.";
    exit;
}

// Solo letras y espacios (incluye acentos y ñ)
if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $userName)) {
    echo "El nombre de usuario solo puede contener letras y espacios.";
    exit;
}
// Apellido opcional, pero si viene debe cumplir el mismo criterio
if ($userLastName !== '' && !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $userLastName)) {
    echo "El apellido solo puede contener letras y espacios.";
    exit;
}
// Longitud máxima para nombres
if (strlen($userName) > 50 || strlen($userLastName) > 50) {
    echo "El nombre y apellido no pueden superar 50 caracteres.";
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

// DNI y Teléfono: solo dígitos
if ($userDNI !== '' && !preg_match('/^\d+$/', $userDNI)) {
    echo "El DNI solo puede contener números.";
    exit;
}
if ($userPhone !== '' && !preg_match('/^\d+$/', $userPhone)) {
    echo "El teléfono solo puede contener números.";
    exit;
}
// Longitud máxima para DNI y Teléfono
if (strlen($userDNI) > 15 || strlen($userPhone) > 15) {
    echo "El DNI y el teléfono no pueden superar 15 dígitos.";
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
