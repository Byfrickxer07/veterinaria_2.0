<?php
session_start();

try {
    $conn = new PDO("mysql:host=localhost;dbname=veterinaria", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Consulta para obtener los turnos incluyendo el estado
    $query = "SELECT t.id, t.fecha, t.hora, t.tipo_servicio, m.nombre AS mascota_nombre, t.estado 
              FROM turnos t 
              JOIN mascotas m ON t.mascota_id = m.id 
              WHERE t.user_id = :user_id 
              ORDER BY t.fecha, t.hora";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Turnos</title>
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

        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #027a8d;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-pending {
            color: red;
            font-weight: bold;
        }
        .status-completed {
            color: green;
            font-weight: bold;
        }
        .status-lost {
            color: gray;
            font-weight: bold;
        }
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .edit-btn {
            background-color: #28a745;
        }
        .edit-btn:hover {
            background-color: #218838;
        }
        .cancel-btn {
            background-color: #dc3545;
        }
        .cancel-btn:hover {
            background-color: #c82333;
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
        
    </div>
    <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
    <a href="sacar_turno.php"><i class='bx bx-calendar'></i><span>Sacar turno</span></a>
    <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Gestionar Perfil</span></a>
    <a href="registrar_mascota.php"><i class='bx bx-plus'></i><span>Añadir tus mascotas</span></a>

    <div class="bottom-menu">
        
        <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span> Cerrar Sesión</span></a>
    </div>
</div>

<div class="content">
    <h1>Mis Turnos</h1>

    <?php if (count($turnos) > 0): ?>
        <table>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Servicio</th>
                <th>Mascota</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($turnos as $turno): ?>
                <tr>
                    <td><?php echo htmlspecialchars($turno['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($turno['hora']); ?></td>
                    <td><?php echo htmlspecialchars($turno['tipo_servicio']); ?></td>
                    <td><?php echo htmlspecialchars($turno['mascota_nombre']); ?></td>
                    <td class="status-<?php echo strtolower($turno['estado']); ?>">
                        <?php echo htmlspecialchars($turno['estado']); ?>
                    </td>
                    <td>
                        <a href="editar_turno.php?id=<?php echo $turno['id']; ?>" class="action-btn edit-btn">Editar</a>
                        <a href="cancelar_turno.php?id=<?php echo $turno['id']; ?>" class="action-btn cancel-btn">Cancelar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No tienes turnos.</p>
    <?php endif; ?>
</div>

<script>
    // Código para el menú lateral
</script>

</body>
</html>
