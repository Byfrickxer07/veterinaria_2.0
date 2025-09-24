<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

$mascota_id = isset($_GET['mascota_id']) ? intval($_GET['mascota_id']) : 0;

if ($mascota_id > 0) {
    $stmt = $mysqli->prepare("
        SELECT fecha_consulta, motivo_consulta, diagnostico, procedimientos_realizados, historial_vacunacion, alergias, medicamentos_actuales
        FROM historial_clinico
        WHERE mascota_id = ?
        ORDER BY fecha_consulta DESC
    ");
    $stmt->bind_param("i", $mascota_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historial = "";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $historial .= "<div class='historial-item'>";
            $historial .= "<h2>Fecha de Consulta: " . htmlspecialchars($row['fecha_consulta']) . "</h2>";
            $historial .= "<p><strong>Motivo de Consulta:</strong> " . htmlspecialchars($row['motivo_consulta']) . "</p>";
            $historial .= "<p><strong>Diagnóstico:</strong> " . htmlspecialchars($row['diagnostico']) . "</p>";
            $historial .= "<p><strong>Procedimientos Realizados:</strong> " . htmlspecialchars($row['procedimientos_realizados']) . "</p>";
            $historial .= "<p><strong>Historial de Vacunación:</strong> " . htmlspecialchars($row['historial_vacunacion']) . "</p>";
            $historial .= "<p><strong>Alergias:</strong> " . htmlspecialchars($row['alergias']) . "</p>";
            $historial .= "<p><strong>Medicamentos Actuales:</strong> " . htmlspecialchars($row['medicamentos_actuales']) . "</p>";
            $historial .= "</div>";
        }
    } else {
        $historial = "<p>No se encontraron registros para esta mascota.</p>";
    }

    $stmt->close();
} else {
    $historial = "<p>ID de mascota no válido.</p>";
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Clínico de la Mascota</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
         body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            
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
            position: fixed; 
            top: 0;
            left: 0;
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
            margin-left: 275px; 
            padding: 50px 20px; 
            
            height: 100vh;
            transition: padding 0.3s;
        }

        h1 {
            text-align: center;
            color: #03879C;
            margin-top: 20px;
            font-size: 24px;
        }
        .historial-item {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .historial-item h2 {
            color: #03879C;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .historial-item p {
            font-size: 16px;
            line-height: 1.6;
            margin: 10px 0;
        }
        .historial-item strong {
            color: #03879C;
        }
        /* Desactivar colapso del sidebar en esta página */
        .sidebar .toggle-menu { display: none !important; }
        .sidebar.collapsed { width: 275px !important; }
        .sidebar.collapsed .user-name,
        .sidebar.collapsed span { display: inline !important; }
    </style>
</head>
<body>
    
<div class="sidebar">
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Profile Image" class="profile-image">
        </div>
        <div class="toggle-menu">
            <i class='bx bx-chevron-left'></i>
        </div>
        <a href="doctor_dashboard.php"><i class='bx bxs-dashboard'></i><span>Inicio</span></a>
        <a href="gestionar_usudoc.php"><i class='bx bx-user'></i><span>Gestionar Usuarios</span></a>
        <a href="gestionar_turnosdoc.php"><i class='bx bx-calendar'></i><span>Gestión de Turnos</span></a>
        <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>

    <div  class="content" >
    <h1>Historial Clínico de la Mascota</h1>
    <?php echo $historial; ?>
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
