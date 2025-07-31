<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $tipo_servicio = $_POST['tipo_servicio'];
    $mascota_id = $_POST['mascota'];
    $user_id = $_SESSION['user_id']; 

    // Validar fecha y hora
    $fecha_actual = date('Y-m-d');
    if ($fecha < $fecha_actual) {
        $mensaje = "<p>La fecha seleccionada no tiene que ser pasada.</p>";
    } else if (!isset($fecha) || !isset($hora) || !isset($tipo_servicio) || !isset($mascota_id)) {
        $mensaje = "<p>Faltan datos del formulario.</p>";
    } else if ($hora < '08:00' || $hora > '18:00') {
        $mensaje = "<p>El horario seleccionado está fuera del rango permitido (8 AM - 6 PM).</p>";
    } else {
        $query = "SELECT * FROM turnos WHERE fecha = ? AND hora = ? AND tipo_servicio = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $fecha, $hora, $tipo_servicio);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $mensaje = "<p>Lo siento, ya existe un turno para el mismo servicio en ese horario. Por favor, selecciona otro.</p>";
        } else {
            $query = "INSERT INTO turnos (user_id, mascota_id, fecha, hora, tipo_servicio) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("iisss", $user_id, $mascota_id, $fecha, $hora, $tipo_servicio);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $mensaje = "<p>Turno reservado con éxito.</p>";
                } else {
                    $mensaje = "<p>No se pudo reservar el turno. Por favor, intente de nuevo.</p>";
                }
                $stmt->close();
            } else {
                $mensaje = "<p>Error en la consulta: " . $conn->error . "</p>";
            }
        }
    }
}

$user_id = $_SESSION['user_id'];
$query = "SELECT id, nombre FROM mascotas WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$mascotas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Turno</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            padding: 50px 20px;
            text-align: center;
            height: 100vh;
            margin-left: 275px;
            transition: padding 0.3s, margin-left 0.3s;
        }

        .sidebar.collapsed ~ .content {
            margin-left: 75px; 
        }

        h1 {
            margin-top: 0;
            color: #027a8d;
            font-size: 32px;
        }

        .form-reserva-turno {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ecf0f1;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }

        .form-reserva-turno .form-group {
            margin-bottom: 20px;
        }

        .form-reserva-turno label {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
            color: #333;
        }

        .form-reserva-turno input,
        .form-reserva-turno select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-reserva-turno input:focus,
        .form-reserva-turno select:focus {
            border-color: #027a8d;
        }

        .btn-reservar {
            background-color: #027a8d;
            color: #ecf0f1;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-reservar:hover {
            background-color: #025162;
        }

        .mensaje {
            margin-top: 20px;
            font-size: 18px;
        }

        .btn-volver {
            background-color: #e74c3c;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-volver:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="toggle-menu">
        <i class='bx bx-chevron-left' id="menu-toggle"></i> 
        </div>
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Perfil" class="profile-image">
            
        </div>
        <a href="client_dashboard.php"><i class='bx bx-home'></i><span> Inicio</span></a>
    <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Ver Turnos</span></a>
    <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Gestionar Perfil</span></a>
    <a href="registrar_mascota.php"><i class='bx bx-plus'></i><span>Añadir tus mascotas</span></a>

        <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>


    <div class="content">
        <h1>Reservar Turno</h1>
        <form method="POST" class="form-reserva-turno">
            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required>
            </div>
            <div class="form-group">
                <label for="hora">Hora:</label>
                <input type="time" id="hora" name="hora" required>
            </div>
            <div class="form-group">
                <label for="tipo_servicio">Tipo de Servicio:</label>
                <select id="tipo_servicio" name="tipo_servicio" required>
                    <option value="cirugia">Cirugía</option>
                    <option value="castracion">Castración</option>
                    <option value="baño">Baño</option>
                </select>
            </div>
            <div class="form-group">
                <label for="mascota">Mascota:</label>
                <select id="mascota" name="mascota" required>
                    <?php foreach ($mascotas as $mascota): ?>
                        <option value="<?= $mascota['id'] ?>"><?= htmlspecialchars($mascota['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-reservar">Reservar</button>
         
        </form>
        <div class="mensaje"><?= $mensaje ?></div>
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
