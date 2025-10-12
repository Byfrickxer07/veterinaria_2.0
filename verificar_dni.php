<?php
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

$dni = isset($_POST['dni']) ? trim($_POST['dni']) : '';

if ($dni) {
    $sql = "SELECT * FROM user WHERE dni='$dni'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "existe";
    } else {
        echo "no_existe";
    }
} else {
    echo "no_existe";
}

$conn->close();
?>
