<?php
session_start();

$usuario_logueado = isset($_SESSION['usuario_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Panel de Usuario</title>

    
   
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .sidebar {
            width: 275px;
            background-color: #2c3e50;
            color: #ecf0f1;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .sidebar a {
            display: block;
            width: 100%;
            padding: 15px 2px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 18px;
            text-align: center;
            border-bottom: 1px solid #34495e;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
        }

        h1 {
            margin-top: 0;
        }

        .session-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: #ecf0f1;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            border-radius: 4px;
            width: 80%;
            display: block;
        }

        .session-button.logout {
            background-color: #e74c3c;
        }

        .session-button.logout:hover {
            background-color: #c0392b;
        }

        .session-button.login:hover {
            background-color: #2980b9;
        }

       
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal a {
            display: block;
            margin: 10px 0;
            padding: 10px;
            background-color: #3498db;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .modal a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <h2>Panel de Usuario</h2>
            <a href="sacar_turno.php" onclick="return verificarSesion()">Sacar Turno</a>
            <a href="ver_turnos.php">Ver Turnos</a>
            <a href="perfil.php">Gestionar Perfil</a>
            <a href="registrar_mascota.php" onclick="return verificarSesion()">Añadir tus mascotas</a>
        </div>
        <?php if ($usuario_logueado): ?>
            <a href="logout.php" class="session-button logout">Cerrar Sesión</a>
        <?php else: ?>
            <a href="login.php" class="session-button login">Iniciar Sesión</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <h1>Bienvenido al Sistema de Gestión de la Veterinaria</h1>
        <p>Seleccione una opción del menú a la izquierda para comenzar.</p>
    </div>

   
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Iniciar Sesión o Registrarse</h2>
            <p>Por favor, inicie sesión o regístrese para continuar.</p>
            <a href="login.php">Iniciar Sesión</a>
            <a href="register.php">Registrarse</a>
        </div>
    </div>

    <script>
        function verificarSesion() {
            <?php if (!$usuario_logueado): ?>
                
                document.getElementById('loginModal').style.display = "block";
                return false;
            <?php else: ?>
               
                return true;
            <?php endif; ?>
        }

        function cerrarModal() {
            document.getElementById('loginModal').style.display = "none";
        }
    </script>

</body>
</html>
