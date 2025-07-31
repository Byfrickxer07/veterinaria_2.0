<?php
include 'db.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "veterinaria");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}


$fecha = $_POST['fecha'];
$hora = $_POST['hora'];
$tipo_servicio = $_POST['tipo_servicio'];
$mascota_id = $_POST['mascota'];
$user_id = $_SESSION['user_id'];


$hora_inicio = strtotime('08:00');
$hora_fin = strtotime('18:00');
$hora_seleccionada = strtotime($hora);

if ($hora_seleccionada < $hora_inicio || $hora_seleccionada > $hora_fin) {
    echo "<p>Lo siento, solo puedes reservar turnos entre las 8:00 AM y las 6:00 PM.</p>";
    exit();
}


$query = "SELECT * FROM turnos WHERE fecha = ? AND hora = ? AND tipo_servicio = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $fecha, $hora, $tipo_servicio);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  
    echo "<p>Lo siento, ya existe un turno para ese servicio en ese horario. Por favor, selecciona otro.</p>";
} else {
   
    $query = "INSERT INTO turnos (user_id, mascota_id, fecha, hora, tipo_servicio) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $user_id, $mascota_id, $fecha, $hora, $tipo_servicio);

    if ($stmt->execute()) {
        echo "<p>Turno registrado exitosamente.</p>";
    } else {
        echo "<p>Hubo un error al registrar el turno. Por favor, intenta nuevamente.</p>";
    }
}

$stmt->close();
$conn->close();
?>
