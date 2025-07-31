<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$alertMessage = '';
$selectedUserId = null;
$selectedPetId = null;
$historyMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["view_pets"])) {
        $selectedUserId = $_POST["user_id"];
    }
    if (isset($_POST["add_history"])) {
        $selectedPetId = $_POST["pet_id"];
    }

    if (isset($_POST["save_history"]) && $selectedPetId) {
        // Guardar historial clínico
        $selectedPetId = $_POST["pet_id"];
        $fecha_consulta = $_POST["fecha_consulta"];
        $motivo_consulta = $_POST["motivo_consulta"];
        $diagnostico = $_POST["diagnostico"];
        $procedimientos_realizados = $_POST["procedimientos_realizados"];
        $historial_vacunacion = $_POST["historial_vacunacion"];
        $alergias = $_POST["alergias"];
        $medicamentos_actuales = $_POST["medicamentos_actuales"];
        
        $sql = "INSERT INTO historial_clinico (mascota_id, fecha_consulta, motivo_consulta, diagnostico, procedimientos_realizados, historial_vacunacion, alergias, medicamentos_actuales)
        VALUES ('$selectedPetId', '$fecha_consulta', '$motivo_consulta', '$diagnostico', '$procedimientos_realizados', '$historial_vacunacion', '$alergias', '$medicamentos_actuales')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Historial clínico guardado exitosamente.";
        } else {
            echo "Error al guardar el historial clínico: " . $conn->error;
            }    
        
    }
}

// Obtener clientes
$sql = "SELECT id, nombre_usuario, correo_electronico FROM user WHERE rol = 'cliente'";
$result = $conn->query($sql);

// Obtener mascotas del usuario seleccionado
$pets = [];
if ($selectedUserId) {
    $sqlPets = "SELECT id, nombre, especie, raza, edad FROM mascotas WHERE user_id = $selectedUserId";
    $petsResult = $conn->query($sqlPets);
    if ($petsResult->num_rows > 0) {
        while ($row = $petsResult->fetch_assoc()) {
            $pets[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
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
            background-color: #5c5c5c;
        }

        .alert {
            background-color: #ffdddd;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .button {
            background-color: #03879C;
            color: #fff;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }

        .button:hover {
            background-color: #026b7c;
        }

        .modal {
            display: none; /* Ocultar el modal por defecto */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Fondo oscuro semi-transparente */
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .modal-content h2 {
            color: #03879C; /* Color del título */
            margin-top: 0;
        }

        .close {
            color: #03879C;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #005f6b;
            text-decoration: none;
            cursor: pointer;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            height: 80px;
            resize: vertical; /* Permite redimensionar verticalmente */
        }

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
        <a href="gestionar_turnosdoc.php"><i class='bx bx-calendar'></i><span>Gestión de Turnos</span></a>
      
        <div class="bottom-menu">
          <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>

    <div class="content">
        <?php if ($alertMessage): ?>
            <div class="alert <?= strpos($alertMessage, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                <?= $alertMessage ?>
            </div>
        <?php endif; ?>

        <h1>Gestión de Clientes</h1>

        <table>
            <tr>
              
                <th>Nombre de Usuario</th>
                <th>Correo Electrónico</th>
                <th>Acciones</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                       
                        <td><?= $row["nombre_usuario"] ?></td>
                        <td><?= $row["correo_electronico"] ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <input type="submit" name="view_pets" value="Ver Mascotas" class="button">
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No se encontraron clientes.</td>
                </tr>
            <?php endif; ?>
        </table>

        <?php if ($selectedUserId && !empty($pets)): ?>
            <h2>Mascotas Registradas</h2>
            <table>
                <tr>
                    <th>Nombre</th>
                    <th>Especie</th>
                    <th>Raza</th>
                    <th>Edad</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($pets as $pet): ?>
                    <tr>
                        <td><?= $pet["nombre"] ?></td>
                        <td><?= $pet["especie"] ?></td>
                        <td><?= $pet["raza"] ?></td>
                        <td><?= $pet["edad"] ?></td>
                        <td>
                            <button class="button" onclick="openModal(<?= $pet['id'] ?>)">Agregar Historial</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

        <!-- Modal -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Agregar Historial Clínico</h2>
                <form method="post" action="guardar_historial.php">
                    <input type="hidden" id="modal_pet_id" name="pet_id">
                    <label for="date">Fecha de Consulta:</label>
                    <input type="date" name="fecha_consulta" required><br><br>
                    <label for="reason">Motivo de Consulta:</label>
                    <textarea name="motivo_consulta" required></textarea><br><br>
                    <label for="diagnosis">Diagnóstico:</label>
                    <textarea name="diagnostico"></textarea><br><br>
                    <label for="procedures">Procedimientos Realizados:</label>
                    <textarea name="procedimientos_realizados"></textarea><br><br>
                    <label for="vaccination_history">Historial de Vacunación:</label>0
                    <textarea name="historial_vacunacion"></textarea><br><br>
                    <label for="allergies">Alergias:</label>
                    <textarea name="alergias"></textarea><br><br>
                    <label for="current_medications">Medicamentos Actuales:</label>
                    <textarea name="medicamentos_actuales"></textarea><br><br>
                    <button type="submit" class="button" name="save_history">Guardar Historial</button>
                </form>
            </div>
        </div>
    <script>



        function openModal(petId) {
            document.getElementById("modal_pet_id").value = petId;
            document.getElementById("myModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("myModal").style.display = "none";
        }
    </script>
</body>
</html>
