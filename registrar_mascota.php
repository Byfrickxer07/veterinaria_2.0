<?php
include 'db.php';
session_start();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'registrar_mascota') {
        if (isset($_POST['nombre_mascota']) && isset($_POST['especie']) && isset($_POST['raza']) && isset($_POST['edad'])) {
            $nombre_mascota = $_POST['nombre_mascota'];
            $especie = $_POST['especie'];
            $raza = $_POST['raza'];
            $edad = $_POST['edad'];
            $user_id = $_SESSION['user_id']; 

            $query = "INSERT INTO mascotas (user_id, nombre, especie, raza, edad) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("isssi", $user_id, $nombre_mascota, $especie, $raza, $edad);
                $stmt->execute();
                $stmt->close();
                $mensaje = "<p>Mascota registrada con éxito.</p>";
            } else {
                $mensaje = "<p>Error en la consulta: " . $conn->error . "</p>";
            }
        } else {
            $mensaje = "<p>Por favor, completa todos los campos.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Mascota</title>
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
        }

.container {
    background: #ffffff;
    border-radius: 15px;
    padding: 40px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    text-align: center;
    margin: 0 auto;
    transition: transform 0.3s ease-in-out;
}

.container:hover {
    transform: translateY(-10px);
}

h1 {
    color: #333;
    font-size: 28px;
    margin-bottom: 30px;
    font-weight: 700;
}

label {
    display: block;
    text-align: left;
    font-size: 16px;
    color: #555;
    margin-bottom: 10px;
    font-weight: 500;
}

input[type="text"] {
    width: calc(100% - 20px);
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    background: #fafafa;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input[type="text"]:focus {
    border-color: #007BFF;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
    background: #fff;
    outline: none;
}

button {
    width: 100%;
    padding: 14px;
    background:  #027a8d;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.3s ease;
}

button:hover {
    background: #025b6c;
    transform: scale(1.02);
}

.back-button {
    margin-top: 20px;
}

.back-button button {
    background: transparent;
    color: #007BFF;
    border: 2px solid #007BFF;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    transition: background 0.3s ease, color 0.3s ease;
}

.back-button button:hover {
    background: #007BFF;
    color: #ffffff;
}


        .message {
            color: green;
            margin-top: 20px;
        }

        .error {
            color: red;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="toggle-menu">
            <i class='bx bx-chevron-left' id="menu-toggle"></i> 
        </div>
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
            <h2 class="user-name">Bienvenido</h2>
        </div>
        <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
        <a href="sacar_turno.php"><i class='bx bx-calendar'></i><span>Sacar turno</span></a>
        <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Gestionar Perfil</span></a>
        <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Ver Turnos</span></a>
        <div class="bottom-menu">
            <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesion</span></a>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <h1>Registrar Mascota</h1>
            <form action="" method="POST">
                <input type="hidden" name="action" value="registrar_mascota">
                <label for="nombre_mascota">Nombre de la Mascota</label>
                <input type="text" id="nombre_mascota" name="nombre_mascota" required>

                <label for="especie">Especie</label>
                <input type="text" id="especie" name="especie" required>

                <label for="raza">Raza</label>
                <input type="text" id="raza" name="raza" required>

                <label for="edad">Edad</label>
                <input type="text" id="edad" name="edad" required>

                <button type="submit">Registrar Mascota</button>
            </form>
            
            <!-- Mostrar el mensaje aquí -->
            <?php if ($mensaje) : ?>
                <div class="<?= strpos($mensaje, 'Error') !== false ? 'error' : 'message' ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>
        </div>
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
