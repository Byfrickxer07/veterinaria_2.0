<?php 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
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
$sql = "SELECT nombre_usuario FROM user WHERE id='$userId'";
$result = $conn->query($sql);

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
    <title>Veterinaria - Panel de Usuario</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            overflow: hidden;
            background: linear-gradient(135deg, #f4f4f9 0%, #e8eef2 100%);
            color: #2c3e50;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .dark-mode {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #e0e0e0;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #025162 0%, #034854 100%);
            backdrop-filter: blur(10px);
            color: #ecf0f1;
            padding-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .user-name,
        .sidebar.collapsed span {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.2s ease;
        }
        .sidebar.collapsed .profile-section {
            text-align: center;
        }

        .sidebar .toggle-menu {
            position: absolute;
            top: 25px;
            right: -18px;
            cursor: pointer;
            background: linear-gradient(135deg, #027a8d 0%, #025162 100%);
            padding: 12px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(2, 122, 141, 0.3);
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.1);
            z-index: 1001;
        }

        .sidebar.collapsed .toggle-menu {
            right: -18px;
            transform: rotate(180deg);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 40px;
            transition: all 0.3s ease;
            width: 100%;
            padding: 0 20px;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar.collapsed .profile-image {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: #ecf0f1;
            text-decoration: none;
            padding: 16px 24px;
            width: calc(100% - 30px);
            margin: 0 15px 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .sidebar a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .sidebar a:hover::before {
            left: 100%;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 16px;
            width: 50px;
            margin: 0 15px 12px 15px;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .sidebar.collapsed a:hover {
            transform: translateX(0) scale(1.05);
        }

        .sidebar i {
            margin-right: 12px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed i {
            margin-right: 0;
        }

        .sidebar .toggle-menu:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(2, 122, 141, 0.4);
        }
            display: flex;
            align-items: center;
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
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 50px 20px;
            height: 100vh;
            overflow: hidden;
            text-align: center;
            position: relative;
        }

        .content h1,
        .content p {
            margin: 0;
            font-size: 2em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            line-height: 1.4;
            white-space: nowrap; /* Prevent text from wrapping */
        }

        .content h1 {
            font-size: 2.5em;
            font-weight: bold;
            color: #025162;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px; /* Adjust the vertical positioning */
        }

        .content p {
            font-size: 1.2em;
            color: #555;
            margin-top: 10px; /* Adjust the vertical positioning */
            line-height: 1.6;
            max-width: 100%; /* Allow text to use full width */
            overflow: hidden; /* Hide overflowing text */
            border-right: 3px solid #333;
            white-space: nowrap;
            box-sizing: border-box;
            padding-right: 5px; /* Adjust to align the text */
        }
        .typing-text {
            font-size: 1.2em;
            overflow: hidden;
            white-space: nowrap;
            border-right: 3px solid #333;
            animation: typing 4s steps(40, end), blink-caret .75s step-end infinite;
            margin: 0 auto;
            font-family: 'Courier New', Courier, monospace;
            color: #333;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
        }

        @keyframes typing {
            from {
                width: 0;
            }
            to {
                width: 65%;
            }
        }

        @keyframes blink-caret {
            from, to {
                border-color: transparent;
            }
            50% {
                border-color: #333;
            }
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

        .sidebar.collapsed .toggle-switch i {
            display: none; 
        }
        /* Estilos del Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #025162 0%, #034854 100%);
            color: #ecf0f1;
            padding: 30px 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }
        
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }
        
        .profile-image:hover {
            transform: scale(1.05);
        }
        
        .user-name {
            font-size: 1.1rem;
            font-weight: 500;
            margin: 10px 0;
            color: #fff;
        }
        
        .sidebar a {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            padding: 14px 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            margin: 5px 15px;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .sidebar a i {
            margin-right: 15px;
            font-size: 1.4rem;
            min-width: 30px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #4CAF50;
            transform: translateX(5px);
        }
        
        .sidebar a:hover i {
            transform: scale(1.1);
        }
        
        .sidebar a.active {
            background-color: rgba(76, 175, 80, 0.15);
            border-left: 4px solid #4CAF50;
            font-weight: 500;
        }
        
        .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding: 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logout-button {
            color: #ff6b6b !important;
            margin: 0 15px;
            border-radius: 8px;
        }
        
        .logout-button:hover {
            background-color: rgba(255, 107, 107, 0.1) !important;
            border-left: 4px solid #ff6b6b !important;
        }
        
        /* Asegurar que el contenido principal no se oculte detrás del sidebar */
        .content {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 30px;
        }
        
        /* Estilos responsivos */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content {
                margin-left: 0;
                width: 100%;
            }
            
            .mobile-menu-toggle {
                display: block !important;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1100;
                background: #025162;
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                font-size: 1.5rem;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            }
        }
    </style>
    <style>
        /* Override para unificar estilos del sidebar con el dashboard de doctor */
        .sidebar { width: 275px; background-color: #025162; padding-top: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .sidebar a { background-color: #027a8d; padding: 15px 20px; width: calc(100% - 40px); margin-bottom: 15px; border-radius: 12px; font-size: 16px; }
        .sidebar a:hover { background-color: #03485f; transform: translateY(-1px); }
        .sidebar a.active { background-color: #ff6b35; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3); border: 2px solid rgba(255, 255, 255, 0.2); }
        .sidebar i { margin-right: 10px; font-size: 18px; }
        .content { margin-left: 275px; }
    </style>
</head>
<body>

<!-- Botón de menú móvil -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
    <i class='bx bx-menu'></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="profile-section">
        <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
    </div>
    <a href="client_dashboard.php" class="active"><i class='bx bx-home'></i><span>Inicio</span></a>
    <a href="sacar_turno.php"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
    <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
    <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
    <a href="registrar_mascota.php"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
    <a href="historial_cliente.php"><i class='bx bx-notepad'></i><span>Historial Clínico</span></a>
    <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>

    <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
    </div>
</div>

<script>
// Script para el menú móvil
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const sidebar = document.getElementById('sidebar');

if (window.innerWidth <= 992) {
    mobileMenuToggle.style.display = 'block';
    
    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
    
    // Cerrar menú al hacer clic en un enlace
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    });
}

// Actualizar en caso de cambio de tamaño de ventana
window.addEventListener('resize', () => {
    if (window.innerWidth > 992) {
        mobileMenuToggle.style.display = 'none';
        sidebar.classList.remove('active');
    } else {
        mobileMenuToggle.style.display = 'block';
    }
});
</script>

<div class="content">
    <h1>Bienvenido a la Veterinaria</h1>
    <p class="typing-text">Aquí puedes gestionar tus turnos y la información de tus mascotas de manera fácil y rápida.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="alertas_clientes.js"></script>
<script>
    document.getElementById('logout-button').addEventListener('click', (e) => {
        e.preventDefault();
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseás cerrar sesión?',
            icon: 'warning',
            iconColor: '#f5a25d',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#6c63ff',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    });
</script>

</body>
</html>
