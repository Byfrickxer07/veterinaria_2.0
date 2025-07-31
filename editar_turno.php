<?php
session_start();

try {
    $conn = new PDO("mysql:host=localhost;dbname=veterinaria", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    if (isset($_GET['id'])) {
        $turno_id = $_GET['id'];
        $user_id = $_SESSION['user_id'];

       
        $query = "SELECT t.id, t.fecha, t.hora, t.tipo_servicio, t.mascota_id, m.nombre AS mascota_nombre 
                  FROM turnos t 
                  JOIN mascotas m ON t.mascota_id = m.id 
                  WHERE t.id = :id AND t.user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $turno_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $turno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$turno) {
            echo "Turno no encontrado.";
            exit();
        }

       
        $query_mascotas = "SELECT id, nombre FROM mascotas WHERE user_id = :user_id";
        $stmt_mascotas = $conn->prepare($query_mascotas);
        $stmt_mascotas->bindParam(':user_id', $user_id);
        $stmt_mascotas->execute();
        $mascotas = $stmt_mascotas->fetchAll(PDO::FETCH_ASSOC);

    } else {
        echo "ID de turno no especificado.";
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $tipo_servicio = $_POST['tipo_servicio'];
        $mascota_id = $_POST['mascota'];

      
        if ($hora < "08:00" || $hora > "18:00") {
            $mensaje = "La hora debe estar entre 08:00 y 18:00.";
        } else {
        
            $query_check = "SELECT * FROM turnos WHERE fecha = :fecha AND hora = :hora AND tipo_servicio = :tipo_servicio AND id != :id";
            $stmt_check = $conn->prepare($query_check);
            $stmt_check->bindParam(':fecha', $fecha);
            $stmt_check->bindParam(':hora', $hora);
            $stmt_check->bindParam(':tipo_servicio', $tipo_servicio);
            $stmt_check->bindParam(':id', $turno_id);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                $mensaje = "Ya existe un turno en esa fecha y hora para el mismo servicio.";
            } else {
               
                $query_update = "UPDATE turnos SET fecha = :fecha, hora = :hora, tipo_servicio = :tipo_servicio, mascota_id = :mascota_id WHERE id = :id AND user_id = :user_id";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->bindParam(':fecha', $fecha);
                $stmt_update->bindParam(':hora', $hora);
                $stmt_update->bindParam(':tipo_servicio', $tipo_servicio);
                $stmt_update->bindParam(':mascota_id', $mascota_id);
                $stmt_update->bindParam(':id', $turno_id);
                $stmt_update->bindParam(':user_id', $user_id);

                if ($stmt_update->execute()) {
                    header("Location: ver_turnos.php");
                    exit();
                } else {
                    $mensaje = "Hubo un error al actualizar el turno. Por favor, intenta nuevamente.";
                }
            }
        }
    }

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
    <title>Editar Turno</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #333;
        }
        h1 {
            color: #027a8d;
            font-size: 32px;
            text-align: center;
            margin-top: 20px;
        }
        form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        div {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
            color: #027a8d;
        }
        input[type="date"],
        input[type="time"],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="date"]:focus,
        input[type="time"]:focus,
        select:focus {
            border-color: #027a8d;
        }
        button {
            background-color: #027a8d;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            box-sizing: border-box;
        }
        button:hover {
            background-color: #025b6c;
        }
        p {
            text-align: center;
        }
        p a {
            color: #027a8d;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }
        p a:hover {
            color: #025b6c;
        }
    </style>
</head>
<body>
    <h1>Editar Turno</h1>
    <?php if (isset($mensaje)) echo "<p>$mensaje</p>"; ?>
    <form action="editar_turno.php?id=<?php echo $turno['id']; ?>" method="post">
        <div>
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($turno['fecha']); ?>" required>
        </div>
        <div>
            <label for="hora">Hora:</label>
            <input type="time" id="hora" name="hora" value="<?php echo htmlspecialchars($turno['hora']); ?>" min="08:00" max="18:00" required>
        </div>
        <div>
            <label for="tipo_servicio">Tipo de Servicio:</label>
            <select id="tipo_servicio" name="tipo_servicio" required>
                <option value="cirugia" <?php echo $turno['tipo_servicio'] == 'cirugia' ? 'selected' : ''; ?>>Cirugía</option>
                <option value="castracion" <?php echo $turno['tipo_servicio'] == 'castracion' ? 'selected' : ''; ?>>Castración</option>
                <option value="bano" <?php echo $turno['tipo_servicio'] == 'bano' ? 'selected' : ''; ?>>Baño</option>
            </select>
        </div>
        <div>
            <label for="mascota">Selecciona tu mascota:</label>
            <select id="mascota" name="mascota" required>
                <?php foreach ($mascotas as $mascota): ?>
                    <option value="<?php echo $mascota['id']; ?>" <?php echo $mascota['id'] == $turno['mascota_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($mascota['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Actualizar Turno</button>
    </form>
    <p><a href="ver_turnos.php">Volver a Mis Turnos</a></p>
</body>
</html>

