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
    <title>Reservar Turno - Veterinaria</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #025162;
            --primary-dark: #03485f;
            --secondary-color: #027a8d;
            --danger-color: #dc2626;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --bg-light: #f9fafb;
            --radius-md: 8px;
            --radius-lg: 12px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            position: relative;
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .return-link {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            background-color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .return-link:hover {
            background: var(--primary-dark);
        }

        /* Additional styles for the form elements */
        .text-muted {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 2rem;
        }
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(2, 81, 98, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 81, 98, 0.2);
        
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
        }
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="client_dashboard.php" class="return-link">
                <i class='bx bx-arrow-back'></i> Volver
            </a>
            <div class="header-content">
                <h1>Reservar Turno</h1>
                <p>Agenda un turno para tu mascota</p>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo strpos($mensaje, 'éxito') !== false ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo strpos($mensaje, 'éxito') !== false ? '✓' : '✗'; ?></span>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Datos del Turno</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="sacar_turno.php">
                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha del Turno</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" 
                               required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hora" class="form-label">Hora del Turno</label>
                        <input type="time" id="hora" name="hora" class="form-control" 
                               min="08:00" max="18:00" required>
                        <small class="text-muted">Horario de atención: 8:00 AM - 6:00 PM</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_servicio" class="form-label">Tipo de Servicio</label>
                        <select id="tipo_servicio" name="tipo_servicio" class="form-control" required>
                            <option value="" disabled selected>Seleccione un servicio</option>
                            <option value="Consulta General">Consulta General</option>
                            <option value="Vacunación">Vacunación</option>
                            <option value="Peluquería">Peluquería</option>
                            <option value="Cirugía">Cirugía</option>
                            <option value="Desparasitación">Desparasitación</option>
                            <option value="Control">Control</option>
                            <option value="Urgencia">Urgencia</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mascota" class="form-label">Mascota</label>
                        <select id="mascota" name="mascota" class="form-control" required>
                            <option value="" disabled selected>Seleccione una mascota</option>
                            <?php foreach ($mascotas as $mascota): ?>
                                <option value="<?php echo $mascota['id']; ?>">
                                    <?php echo htmlspecialchars($mascota['nombre']); ?>
                                    (<?php echo htmlspecialchars($mascota['especie']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-calendar-check'></i> Reservar Turno
                    </button>
                </form>
            </div>
        </div>
                <i class='bx bx-arrow-back'></i> Volver al Inicio
            </a>
            <div class="header-content">
                <h1>Reservar Turno</h1>
                <p>Agenda un turno para tu mascota</p>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo strpos($mensaje, 'éxito') !== false ? 'alert-success' : 'alert-error'; ?>">
                <span><?php echo strpos($mensaje, 'éxito') !== false ? '✓' : '✗'; ?></span>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Datos del Turno</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="sacar_turno.php">
                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha del Turno</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" 
                               required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hora" class="form-label">Hora del Turno</label>
                        <input type="time" id="hora" name="hora" class="form-control" 
                               min="08:00" max="18:00" required>
                        <small class="text-muted">Horario de atención: 8:00 AM - 6:00 PM</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_servicio" class="form-label">Tipo de Servicio</label>
                        <select id="tipo_servicio" name="tipo_servicio" class="form-control" required>
                            <option value="" disabled selected>Seleccione un servicio</option>
                            <option value="Consulta General">Consulta General</option>
                            <option value="Vacunación">Vacunación</option>
                            <option value="Peluquería">Peluquería</option>
                            <option value="Cirugía">Cirugía</option>
                            <option value="Desparasitación">Desparasitación</option>
                            <option value="Control">Control</option>
                            <option value="Urgencia">Urgencia</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="mascota" class="form-label">Mascota</label>
                        <select id="mascota" name="mascota" class="form-control" required>
                            <option value="" disabled selected>Seleccione una mascota</option>
                            <?php foreach ($mascotas as $mascota): ?>
                                <option value="<?php echo $mascota['id']; ?>">
                                    <?php echo htmlspecialchars($mascota['nombre']); ?> 
                                    (<?php echo htmlspecialchars($mascota['especie']); ?> - <?php echo htmlspecialchars($mascota['raza']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-calendar-check'></i> Reservar Turno
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Configurar la fecha mínima como hoy
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('fecha');
            const hoy = new Date().toISOString().split('T')[0];
            fechaInput.min = hoy;
            
            // Establecer la fecha actual si no hay valor
            if (!fechaInput.value) {
                fechaInput.value = hoy;
            }
        });
    </script>
    <script src="alertas_clientes.js"></script>
</body>
</html>
