<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recoger y validar datos
    $selectedPetId = isset($_POST["pet_id"]) ? intval($_POST["pet_id"]) : null;
    $fecha_consulta = $_POST["fecha_consulta"] ?? null;
    $motivo_consulta = $_POST["motivo_consulta"] ?? null;
    $diagnostico = $_POST["diagnostico"] ?? '';
    $procedimientos_realizados = $_POST["procedimientos_realizados"] ?? '';
    $historial_vacunacion = $_POST["historial_vacunacion"] ?? '';
    $alergias = $_POST["alergias"] ?? '';
    $medicamentos_actuales = $_POST["medicamentos_actuales"] ?? '';

    if (!$selectedPetId || !$fecha_consulta || !$motivo_consulta) {
        $conn->close();
        header("Location: gestionar_usudoc.php?status=error&msg=" . urlencode("Datos incompletos"));
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO historial_clinico (mascota_id, fecha_consulta, motivo_consulta, diagnostico, procedimientos_realizados, historial_vacunacion, alergias, medicamentos_actuales)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param("isssssss", $selectedPetId, $fecha_consulta, $motivo_consulta, $diagnostico, $procedimientos_realizados, $historial_vacunacion, $alergias, $medicamentos_actuales);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: gestionar_usudoc.php?status=ok");
            exit;
        } else {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            header("Location: gestionar_usudoc.php?status=error&msg=" . urlencode($error));
            exit;
        }
    } else {
        $error = $conn->error;
        $conn->close();
        header("Location: gestionar_usudoc.php?status=error&msg=" . urlencode($error));
        exit;
    }
}

$conn->close();
header("Location: gestionar_usudoc.php");
exit;
?>
