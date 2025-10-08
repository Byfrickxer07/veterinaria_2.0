<?php
// Maneja creación y edición (multipart/form-data) y guarda imagen en uploads/
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

// Crear carpeta uploads si no existe
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Campos
$id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
$name = $_POST['name'] ?? '';
$type = $_POST['type'] ?? '';
$breed = $_POST['breed'] ?? '';
$age = $_POST['age'] ?? '';
$gender = $_POST['gender'] ?? '';
$size = $_POST['size'] ?? '';
$description = $_POST['description'] ?? '';
$location = $_POST['location'] ?? '';
$address = $_POST['address'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$status = $_POST['status'] ?? '';

// Validación mínima
if ($name === '' || $type === '' || $breed === '') {
    http_response_code(400);
    echo 'Faltan campos obligatorios';
    exit;
}

// Manejo de imagen
$imagePath = null; // relativo: 'uploads/filename.ext'
$newImageUploaded = isset($_FILES['image_file']) && is_uploaded_file($_FILES['image_file']['tmp_name']);

if ($id) {
    // Obtener imagen actual
    $res = $conn->query("SELECT imagen FROM adopcion WHERE id = " . $id . " LIMIT 1");
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        $imagePath = $row['imagen'];
    }
}

if ($newImageUploaded) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES['image_file']['tmp_name']);
    if (!isset($allowed[$mime])) {
        http_response_code(400);
        echo 'Formato de imagen no permitido';
        exit;
    }
    $ext = $allowed[$mime];
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_FILENAME)));
    $finalName = $safeName . '_' . time() . '.' . $ext;
    $target = $uploadDir . DIRECTORY_SEPARATOR . $finalName;
    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
        http_response_code(500);
        echo 'No se pudo guardar la imagen';
        exit;
    }
    $imagePath = 'uploads/' . $finalName;
}

if ($id) {
    // Update
    $stmt = $conn->prepare("UPDATE adopcion SET nombre=?, tipo=?, raza=?, edad=?, genero=?, tamano=?, descripcion=?, imagen=?, refugio=?, direccion=?, telefono=?, email=?, estado=? WHERE id=?");
    $imgToSave = $imagePath; // conserva previa si no se subió nueva
    $stmt->bind_param(
        'sssssssssssssi',
        $name, $type, $breed, $age, $gender, $size, $description, $imgToSave, $location, $address, $phone, $email, $status, $id
    );
    $ok = $stmt->execute();
    $stmt->close();
    echo $ok ? 'ok' : ('error: ' . $conn->error);
} else {
    // Insert
    $stmt = $conn->prepare("INSERT INTO adopcion (nombre, tipo, raza, edad, genero, tamano, descripcion, imagen, refugio, direccion, telefono, email, estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        'sssssssssssss',
        $name, $type, $breed, $age, $gender, $size, $description, $imagePath, $location, $address, $phone, $email, $status
    );
    $ok = $stmt->execute();
    $stmt->close();
    echo $ok ? 'ok' : ('error: ' . $conn->error);
}

$conn->close();
