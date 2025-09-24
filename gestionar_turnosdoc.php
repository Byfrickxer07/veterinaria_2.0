<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "veterinaria");

if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

// Consulta para obtener todos los turnos
$sql = "SELECT t.id AS turno_id, t.fecha, t.hora, t.tipo_servicio, m.id AS mascota_id, m.nombre AS mascota, u.nombre_usuario AS cliente, t.estado
        FROM turnos t
        JOIN mascotas m ON t.mascota_id = m.id
        JOIN user u ON t.user_id = u.id";

$result = $mysqli->query($sql);

if ($result === FALSE) {
    die("Error en la consulta: " . $mysqli->error);
}

if ($result->num_rows === 0) {
    $no_turnos = true;
} else {
    $no_turnos = false;
}

// Procesar la solicitud para marcar un turno como terminado
if (isset($_POST['mark_completed'])) {
    $turno_id = $_POST['turno_id'];
    $update_sql = "UPDATE turnos SET estado = 'terminado' WHERE id = ?";
    $stmt = $mysqli->prepare($update_sql);
    $stmt->bind_param('i', $turno_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']); // Redirigir para evitar el envío del formulario múltiples veces
    exit;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria - Turnos</title>
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
            text-align: center;
            margin: 20px 0;
        }
        table {
            width: 100%;
            margin: 0 auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);            
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #03879C;
            color: #fff;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-completed {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: red;
            font-weight: bold;
        }
        .action-links a {
            color: #03879C;
            text-decoration: none;
            margin-right: 10px;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .mark-completed {
            background-color: #03879C;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        .mark-completed:hover {
            background-color: #026c82;
        }
        .turnos{
            margin-left: 5vw
        }
       
    </style>
    <style>
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
       
        <a href="gestionar_usudoc.php"><i class='bx bxs-user'></i><span>Gestión de Usuarios</span></a>
        <div class="bottom-menu">
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>

<div class="contec" >
    <h1>Lista de Turnos</h1>
    <div class="turnos">
    <?php if ($no_turnos): ?>
        <p>No hay turnos disponibles.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                  
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Servicio</th>
                    <th>Mascota</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Historial Médico</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      
                        <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($row['hora']); ?></td>
                        <td><?php echo htmlspecialchars($row['tipo_servicio']); ?></td>
                        <td><?php echo htmlspecialchars($row['mascota']); ?></td>
                        <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                        <td class="<?php echo htmlspecialchars($row['estado']) === 'terminado' ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo htmlspecialchars($row['estado']); ?>
                        </td>
                        <td class="action-links">
                            <a href="historial_mascota.php?mascota_id=<?php echo htmlspecialchars($row['mascota_id']); ?>">Ver Historial</a>
                        </td>
                        <td>
                            <?php if (htmlspecialchars($row['estado']) !== 'terminado'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="turno_id" value="<?php echo htmlspecialchars($row['turno_id']); ?>">
                                    <button type="submit" name="mark_completed" class="mark-completed">Marcar como Terminado</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
