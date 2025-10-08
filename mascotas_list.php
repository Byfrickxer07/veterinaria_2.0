<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => $conn->connect_error]);
    exit;
}

$sql = "SELECT id, nombre AS name, tipo AS type, raza AS breed, edad AS age, genero AS gender, tamano AS size, descripcion, imagen AS image, refugio AS location, direccion AS address, telefono AS phone, email, estado AS status FROM adopcion ORDER BY id DESC";
$res = $conn->query($sql);

$rows = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
}

echo json_encode(["pets" => $rows]);
$conn->close();
