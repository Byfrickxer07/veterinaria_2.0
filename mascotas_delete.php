<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo "Error de conexión: " . $conn->connect_error;
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'id inválido';
    exit;
}

// Obtener ruta de imagen para intentar borrar
$imagePath = null;
$res = $conn->query("SELECT imagen FROM adopcion WHERE id = $id LIMIT 1");
if ($res && $res->num_rows) {
    $row = $res->fetch_assoc();
    $imagePath = $row['imagen'];
}

$ok = $conn->query("DELETE FROM adopcion WHERE id = $id");
if ($ok) {
    if ($imagePath && strpos($imagePath, 'uploads/') === 0) {
        $file = __DIR__ . DIRECTORY_SEPARATOR . $imagePath;
        if (is_file($file)) {@unlink($file);} // best effort
    }
    echo 'ok';
} else {
    echo 'error: ' . $conn->error;
}

$conn->close();
