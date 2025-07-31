<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$alertMessage = '';
$selectedUserId = null;
$selectedPetId = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST["view_pets"])) {
        $selectedUserId = $_POST["user_id"];
        
    }
    if (isset($_POST["add_history"])) {
        $selectedPetId = $_POST["pet_id"];
        echo("a");
        echo($selectedPetId);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["pet_id"])) {
            $selectedPetId = $_POST["pet_id"];
            echo "Pet ID: " . $selectedPetId;
        } else {
            echo "No pet_id received.";
        }
    }

    if (isset($_POST["save_history"]) && $selectedPetId) {
        // Guardar historial clínico
        $selectedPetId = $_POST["pet_id"];
        $fecha_consulta = $_POST["fecha_consulta"];
        $motivo_consulta = $_POST["motivo_consulta"];
        $diagnostico = $_POST["diagnostico"];
        $procedimientos_realizados = $_POST["procedimientos_realizados"];
        $historial_vacunacion = $_POST["historial_vacunacion"];
        $alergias = $_POST["alergias"];
        $medicamentos_actuales = $_POST["medicamentos_actuales"];
        
        $stmt = $conn->prepare("INSERT INTO historial_clinico (mascota_id, fecha_consulta, motivo_consulta, diagnostico, procedimientos_realizados, historial_vacunacion, alergias, medicamentos_actuales)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $selectedPetId, $fecha_consulta, $motivo_consulta, $diagnostico, $procedimientos_realizados, $historial_vacunacion, $alergias, $medicamentos_actuales);
        
        if ($stmt->execute()) {
            $alertMessage = "Historial clínico guardado exitosamente.";
            echo ($alertMessage);
        } else {
            $alertMessage = "Error al guardar el historial clínico: " . $stmt->error;
            echo ($alertMessage);
        }
        
        $stmt->close();
    }
}
$conn->close();
header("Location: gestionar_usudoc.php");
?>
