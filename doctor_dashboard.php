<?php 
session_start();

// Verificar si el usuario ha iniciado sesión y tiene el rol 'doctor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];

// Usar prepared statements para evitar inyecciones SQL
$sql = $conn->prepare("SELECT nombre_usuario FROM user WHERE id = ?");
$sql->bind_param("i", $userId);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nombre_usuario = $row['nombre_usuario'];
} else {
    $nombre_usuario = "Usuario";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Panel de Doctor</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        overflow: hidden;
        background-color: #f4f4f9;
        color: #333;
        transition: background-color 0.3s, color 0.3s;
    }

    .dark-mode {
        background-color: #1c1c1c;
        color: #e0e0e0;
    }

    .sidebar {
        width: 275px;
        background-color: #025162;
        color: #ecf0f1;
        padding-top: 40px; 
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100vh;
        transition: width 0.3s, background-color 0.3s;
        position: relative;
    }

    .sidebar.collapsed {
        width: 75px;
    }

    .sidebar.collapsed .user-name,
    .sidebar.collapsed span {
        display: none;
    }

    .sidebar.collapsed .profile-section {
        text-align: center;
    }

    .sidebar .toggle-menu {
        position: absolute;
        top: 30px; 
        right: -15px; 
        cursor: pointer;
        background-color: #027a8d; 
        padding: 10px;
        border-radius: 50%; 
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); 
        transition: right 0.3s ease, background-color 0.3s;
    }

    .sidebar.collapsed .toggle-menu {
        right: -15px; 
    }

    .profile-section {
        text-align: center;
        margin-bottom: 20px; 
        transition: margin-bottom 0.3s;
    }

    .profile-image {
        width: 120px; 
        height: 120px; 
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
        transition: width 0.3s, height 0.3s;
    }

    .sidebar.collapsed .profile-image {
        width: 60px; 
        height: 60px; 
    }

    .sidebar a {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        color: #ecf0f1;
        text-decoration: none;
        padding: 15px 20px;
        width: calc(100% - 40px);
        margin-bottom: 15px; 
        background-color: #027a8d;
        border-radius: 12px;
        font-size: 16px;
        transition: background-color 0.3s, padding 0.3s;
        box-sizing: border-box;
    }

    .sidebar.collapsed a {
        justify-content: center;
        padding: 15px;
    }

    .sidebar a:hover {
        background-color: #03485f;
    }

    .sidebar i {
        margin-right: 10px;
    }

    .sidebar.collapsed i {
        margin-right: 0;
    }

    .sidebar .bottom-menu {
        margin-top: auto;
        width: 100%;
        padding-bottom: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .content {
        flex-grow: 1;
        padding: 50px 20px; 
        text-align: center;
        height: 100vh;
        transition: padding 0.3s;
    }

    h1 {
        margin-top: 0;
    }

    .toggle-switch-container {
        background-color: transparent; 
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        padding: 0;
    }

    .toggle-switch {
        display: flex;
        align-items: center;
        cursor: pointer;
        gap: 5px;
    }

    .toggle-switch i {
        color: #fff; 
    }

    .toggle-switch input {
        display: none;
    }

    .toggle-switch label {
        background-color: #ccc;
        border-radius: 15px;
        cursor: pointer;
        display: inline-block;
        height: 24px;
        position: relative;
        width: 50px;
    }

    .toggle-switch label::after {
        background-color: #fff;
        border-radius: 50%;
        content: '';
        height: 20px;
        left: 2px;
        position: absolute;
        top: 2px;
        transition: all 0.3s;
        width: 20px;
    }

    .toggle-switch input:checked + label {
        background-color: #66bb6a;
    }

    .toggle-switch input:checked + label::after {
        transform: translateX(26px);
    }

    .toggle-switch span {
        color: #fff;
        font-size: 14px;
    }

    .dark-mode .sidebar {
        background-color: #2e2e2e;
    }

    .dark-mode .sidebar a {
        background-color: #3d3d3d;
        color: #e0e0e0;
    }

    .dark-mode .sidebar a:hover {
        background-color: #4c4c4c;
    }

    .dark-mode .profile-section h2 {
        color: #e0e0e0;
    }

    .dark-mode .toggle-switch label {
        background-color: #555;
    }

    .dark-mode .toggle-switch input:checked + label {
        background-color: #ff9800;
    }

    .dark-mode .toggle-switch span {
        color: #e0e0e0;
    }

    .dark-mode .toggle-menu {
        background-color: #444; 
    }

    .logout-button {
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Desactivar colapso del sidebar en esta página */
    .sidebar .toggle-menu { display: none !important; }
    .sidebar.collapsed { width: 275px !important; }
    .sidebar.collapsed .user-name,
    .sidebar.collapsed span { display: inline !important; }
</style>

<div class="sidebar">
    <div class="toggle-menu">
        <i class='bx bx-chevron-left' id="menu-toggle"></i> 
    </div>
    <div class="profile-section">
        <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
        <h2 class="user-name">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></h2>
    </div>

    <a href="gestionar_usudoc.php"><i class='bx bx-user'></i><span>Gestionar Usuarios</span></a>
    <a href="gestionar_turnosdoc.php"><i class='bx bx-calendar'></i><span>Gestionar Turnos</span></a>
   

    <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
    </div>
</div>

<div class="content">
    <h1>Panel de Doctor</h1>
    <p>Gestiona los usuarios, turnos, historial clínico, y más desde este panel.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="alertas_clientes.js"></script>
<script>
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const body = document.body;

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        menuToggle.classList.toggle('bx-chevron-left');
        menuToggle.classList.toggle('bx-chevron-right');
    });

    darkModeToggle.addEventListener('change', () => {
        body.classList.toggle('dark-mode');
    });

    const logoutButton = document.getElementById('logout-button');
    logoutButton.addEventListener('click', function(event) {
        event.preventDefault();
        Swal.fire({
            title: '¿Estás seguro de que deseas cerrar sesión?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    });
</script>

</body>
</html>
