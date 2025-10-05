<?php
// Simple JSON endpoint: devuelve horas ocupadas para una fecha y servicio
header('Content-Type: application/json; charset=utf-8');

try {
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;
    $servicio = isset($_GET['tipo_servicio']) ? $_GET['tipo_servicio'] : null;
    $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

    if (!$fecha || !$servicio) {
        echo json_encode(['ok' => false, 'error' => 'Parámetros incompletos']);
        exit;
    }

    $pdo = new PDO('mysql:host=localhost;dbname=veterinaria', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT hora FROM turnos WHERE fecha = :fecha AND tipo_servicio = :servicio AND estado = 'Pendiente'";
    if ($excludeId > 0) {
        $sql .= " AND id <> :exclude";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':fecha', $fecha);
    $stmt->bindValue(':servicio', $servicio);
    if ($excludeId > 0) {
        $stmt->bindValue(':exclude', $excludeId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Normalizar a HH:MM
    $ocupadas = array_map(function($h){
        // $h puede venir como HH:MM:SS
        $parts = explode(':', $h);
        return sprintf('%02d:%02d', (int)$parts[0], (int)$parts[1]);
    }, $rows ?: []);

    echo json_encode(['ok' => true, 'ocupadas' => array_values(array_unique($ocupadas))]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
