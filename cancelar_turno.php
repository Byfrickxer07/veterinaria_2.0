<?php
session_start();

try {
    $conn = new PDO("mysql:host=localhost;dbname=veterinaria", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    if (isset($_GET['id'])) {
        $turno_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];

        $query = "DELETE FROM turnos WHERE id = :id AND user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $turno_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            header("Location: ver_turnos.php");
            exit();
        } else {
            echo "Hubo un error al cancelar el turno. Por favor, intenta nuevamente.";
        }
    } else {
        echo "ID de turno no especificado.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
