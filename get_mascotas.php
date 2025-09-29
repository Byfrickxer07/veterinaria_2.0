<?php
header('Content-Type: application/json; charset=utf-8');

$mysqli = new mysqli("localhost", "root", "", "veterinaria");
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida"]);
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Parámetro inválido"]);
    exit;
}

$stmt = $mysqli->prepare("SELECT id, nombre FROM mascotas WHERE user_id = ? ORDER BY nombre ASC");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo preparar la consulta"]);
    exit;
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$mascotas = [];
while ($row = $result->fetch_assoc()) {
    $mascotas[] = [
        'id' => (int)$row['id'],
        'nombre' => $row['nombre'],
    ];
}
$stmt->close();
$mysqli->close();

echo json_encode(["mascotas" => $mascotas]);
