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
        $mensaje = "error:La fecha seleccionada no puede ser pasada.";
    } else if (empty($fecha) || empty($hora) || empty($tipo_servicio) || empty($mascota_id)) {
        $mensaje = "error:Faltan datos del formulario. Por favor complete todos los campos.";
    } else if ($hora < '08:00' || $hora > '18:00') {
        $mensaje = "error:El horario de atención es de 8:00 AM a 6:00 PM.";
    } else {
        // Validar tipo de servicio contra los valores del ENUM en la BD
        $servicios_permitidos = ['vacunacion', 'Control', 'castracion', 'baño'];
        if (!in_array($tipo_servicio, $servicios_permitidos, true)) {
            $mensaje = "error:Tipo de servicio no válido.";
        } else {
        // Definir duración de cada tipo de servicio en minutos
        $duraciones = [
            'vacunacion' => 20,  // 20 minutos
            'Control' => 20,     // 20 minutos
            'castracion' => 60,  // 1 hora
            'baño' => 60         // 1 hora
        ];

        // Obtener la duración del servicio seleccionado
        $duracion = $duraciones[$tipo_servicio] ?? 30; // 30 minutos por defecto
        
        // Calcular hora de inicio y fin del turno solicitado
        $horaInicio = new DateTime($fecha . ' ' . $hora);
        $horaFin = clone $horaInicio;
        $horaFin->modify("+{$duracion} minutes");
        
        // Verificar si la mascota ya tiene un turno que se solape
        $query = "SELECT t.* FROM (
                    SELECT 
                        turnos.*,
                        CASE 
                            WHEN tipo_servicio = 'vacunacion' THEN 20
                            WHEN tipo_servicio = 'Control' THEN 20
                            WHEN tipo_servicio = 'castracion' THEN 60
                            WHEN tipo_servicio = 'baño' THEN 60
                            ELSE 30
                        END as duracion_minutos
                    FROM turnos
                    WHERE mascota_id = ? AND fecha = ?
                ) as t
                WHERE (
                    -- Turno existente empieza antes y termina después del inicio del nuevo turno
                    (t.hora <= ? AND ADDTIME(t.hora, SEC_TO_TIME(t.duracion_minutos * 60)) > ?) OR
                    -- Turno existente empieza durante el nuevo turno
                    (t.hora < ? AND t.hora >= ?)
                )";
                
        $horaInicioStr = $horaInicio->format('H:i:s');
        $horaFinStr = $horaFin->format('H:i:s');
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", 
            $mascota_id, 
            $fecha,
            $horaInicioStr, $horaInicioStr,
            $horaFinStr, $horaInicioStr
        );
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $turno = $result->fetch_assoc();
            $mensaje = "error:La mascota ya tiene un turno programado de " . $turno['tipo_servicio'] . 
                      " que se solapa con este horario. Por favor, elija otra fecha u hora.";
        } else {
            // Verificar disponibilidad del horario para el mismo servicio
            $query = "SELECT * FROM (
                        SELECT 
                            t.*,
                            CASE 
                                WHEN t.tipo_servicio = 'vacunacion' THEN 20
                                WHEN t.tipo_servicio = 'Control' THEN 20
                                WHEN t.tipo_servicio = 'castracion' THEN 60
                                WHEN t.tipo_servicio = 'baño' THEN 60
                                ELSE 30
                            END as duracion_minutos
                        FROM turnos t
                        WHERE t.fecha = ? 
                        AND t.tipo_servicio = ?
                    ) as turnos_con_duracion
                    WHERE (
                        -- Turno existente empieza antes y termina después del inicio del nuevo turno
                        (hora <= ? AND ADDTIME(hora, SEC_TO_TIME(duracion_minutos * 60)) > ?) OR
                        -- Turno existente empieza durante el nuevo turno
                        (hora < ? AND hora >= ?)
                    )";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssss", 
                $fecha, $tipo_servicio,
                $horaInicioStr, $horaInicioStr,
                $horaFinStr, $horaInicioStr
            );
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $mensaje = "error:Ya existe un turno para este servicio en un horario que se solapa. Por favor, elija otra fecha u hora.";
            } else {
                $query = "INSERT INTO turnos (user_id, mascota_id, fecha, hora, tipo_servicio) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);

                if ($stmt) {
                    $stmt->bind_param("iisss", $user_id, $mascota_id, $fecha, $hora, $tipo_servicio);
                    $stmt->execute();
                    if ($stmt->affected_rows > 0) {
                        $mensaje = "success:¡Turno reservado con éxito!";
                    } else {
                        $mensaje = "error:No se pudo reservar el turno. Por favor, intente de nuevo.";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "error:Error en la base de datos: " . $conn->error;
                }
            }
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
$tiene_mascotas = !empty($mascotas);
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
            z-index: 1000;
        }

        .sidebar .toggle-menu:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(2, 122, 141, 0.4);
        }

        .sidebar.collapsed .toggle-menu {
            right: -18px;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
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

        .sidebar .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content {
            flex-grow: 1;
            padding: 40px 30px;
            text-align: center;
            height: 100vh;
            margin-left: 280px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.02);
        }

        .sidebar.collapsed ~ .content {
            margin-left: 80px; 
        }

        h1 {
            margin: 0 0 40px 0;
            background: linear-gradient(135deg, #027a8d 0%, #025162 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
            position: relative;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #027a8d 0%, #025162 100%);
            border-radius: 2px;
        }

        .form-reserva-turno {
            max-width: 550px;
            margin: 0 auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .form-reserva-turno::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #027a8d 0%, #025162 50%, #027a8d 100%);
        }

        .form-reserva-turno:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.3);
        }

        .form-reserva-turno .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-reserva-turno label {
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            transition: all 0.3s ease;
            text-align: left;
        }

        .form-reserva-turno input,
        .form-reserva-turno select {
            width: 100%;
            padding: 16px 20px;
            border-radius: 12px;
            border: 2px solid #e1e8ed;
            background: rgba(255, 255, 255, 0.9);
            box-sizing: border-box;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            outline: none;
        }

        .form-reserva-turno input:focus,
        .form-reserva-turno select:focus {
            border-color: #027a8d;
            background: rgba(255, 255, 255, 1);
            box-shadow: 
                0 0 0 4px rgba(2, 122, 141, 0.1),
                0 4px 20px rgba(2, 122, 141, 0.15);
            transform: translateY(-2px);
        }

        .form-reserva-turno input:hover,
        .form-reserva-turno select:hover {
            border-color: #027a8d;
            background: rgba(255, 255, 255, 1);
        }

        .btn-reservar {
            background: linear-gradient(135deg, #027a8d 0%, #025162 100%);
            color: #ffffff;
            padding: 18px 36px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 4px 15px rgba(2, 122, 141, 0.3);
            position: relative;
            overflow: hidden;
            width: 100%;
            margin-top: 10px;
        }

        .btn-reservar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-reservar:hover::before {
            left: 100%;
        }

        .btn-reservar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(2, 122, 141, 0.4);
            background: linear-gradient(135deg, #028a9f 0%, #025a6b 100%);
        }

        .btn-reservar:active {
            transform: translateY(-1px);
        }

        .mensaje {
            margin-top: 30px;
            font-size: 16px;
            padding: 20px;
            border-radius: 12px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-volver {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: #ffffff;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-volver:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        /* Animaciones de entrada */
        .form-reserva-turno {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .user-name,
            .sidebar span {
                display: none;
            }
            
            .content {
                margin-left: 70px;
                padding: 20px 15px;
            }
            
            .form-reserva-turno {
                padding: 30px 20px;
                margin: 0 10px;
            }
            
            h1 {
                font-size: 28px;
            }
        }

        /* Mejoras para selects */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23027a8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 50px;
        }

        /* Estilos para el mensaje de sin mascotas */
        .no-mascotas-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 2rem;
        }

        .no-mascotas-content {
            background: #fff;
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .no-mascotas-icon {
            font-size: 4rem;
            color: #0284c7;
            margin-bottom: 1.5rem;
        }

        .no-mascotas-content h2 {
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .no-mascotas-text {
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .no-mascotas-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: #0284c7;
            color: white;
        }

        .btn-primary:hover {
            background: #0369a1;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Estilos para el formulario cuando hay mascotas */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Desactivar colapso del sidebar en esta página */
        .sidebar .toggle-menu { display: none !important; }
        .sidebar.collapsed { width: 280px !important; }
        .sidebar.collapsed ~ .content { margin-left: 280px !important; }
        @media (max-width: 768px) {
            .sidebar.collapsed { width: 70px !important; }
            .sidebar.collapsed ~ .content { margin-left: 70px !important; }
            
            .no-mascotas-content {
                padding: 2rem 1.5rem;
            }
            
            .no-mascotas-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
    <div class="sidebar">
        <div class="toggle-menu">
            <i class='bx bx-chevron-left' id="menu-toggle"></i> 
        </div>
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Perfil" class="profile-image">
        </div>
        <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
        <a href="sacar_turno.php" class="active"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
        <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
        <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
        <a href="registrar_mascota.php"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
        <a href="historial_cliente.php"><i class='bx bx-notepad'></i><span>Historial Clínico</span></a>
        <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>

        <div class="bottom-menu">
            <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>

    <div class="content">
        <h1>Reservar Turno</h1>
        
        <?php if (!$tiene_mascotas): ?>
            <div class="no-mascotas-container">
                <div class="no-mascotas-content">
                    <div class="no-mascotas-icon">
                        <i class='bx bx-package'></i>
                    </div>
                    <h2>¡Aún no tienes mascotas registradas!</h2>
                    <p class="no-mascotas-text">Para poder sacar un turno, primero necesitas registrar al menos una mascota en tu perfil.</p>
                    <div class="no-mascotas-buttons">
                        <a href="registrar_mascota.php" class="btn btn-primary">
                            <i class='bx bx-plus'></i> Registrar Mascota
                        </a>
                        <a href="client_dashboard.php" class="btn btn-secondary">
                            <i class='bx bx-home'></i> Volver al Inicio
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="form-container">
                <form method="POST" class="form-reserva-turno">
            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required>
            </div>
            <div class="form-group">
                <label for="hora">Hora:</label>
                <select id="hora" name="hora" class="form-control" required>
                    <option value="">Selecciona el horario</option>
                </select>
                <small class="text-muted">Los horarios disponibles se actualizan según el servicio seleccionado</small>
            </div>
            <div class="form-group">
                <label for="tipo_servicio">Tipo de Servicio:</label>
                <select id="tipo_servicio" name="tipo_servicio" required>
                    <option value="">Selecciona un servicio</option>   
                    <option value="vacunacion">Vacunación</option>
                    <option value="Control">Control</option>
                    <option value="castracion">Castración</option>
                    <option value="baño">Baño</option>
                </select>
            </div>
            <div class="form-group">
                <label for="mascota">Mascota:</label>
                <select id="mascota" name="mascota" required>
                    <option value="">Selecciona tu mascota</option>
                    <?php foreach ($mascotas as $mascota): ?>
                        <option value="<?= $mascota['id'] ?>"><?= htmlspecialchars($mascota['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-reservar">
                <i class='bx bx-calendar-plus' style="margin-right: 8px;"></i>
                Reservar Turno
            </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="alertas_clientes.js"></script>
    <script>
        // Mostrar alertas de PHP
        <?php if (!empty($mensaje)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const [type, ...messageParts] = '<?php echo $mensaje; ?>'.split(':');
                const message = messageParts.join(':');
                
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });

                if (type === 'success') {
                    Toast.fire({
                        icon: 'success',
                        title: message,
                        background: '#d4edda',
                        color: '#155724',
                        iconColor: '#28a745'
                    });
                } else if (type === 'error') {
                    Toast.fire({
                        icon: 'error',
                        title: message,
                        background: '#f8d7da',
                        color: '#721c24',
                        iconColor: '#dc3545'
                    });
                }
            });
        <?php endif; ?>

        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            menuToggle.classList.toggle('bx-chevron-left');
            menuToggle.classList.toggle('bx-chevron-right');
        });

        const logoutButton = document.getElementById('logout-button');
        logoutButton.addEventListener('click', function(event) {
            event.preventDefault();
            Swal.fire({
                title: '¿Estás seguro de que deseas cerrar sesión?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#027a8d',
                cancelButtonColor: '#e74c3c',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                background: 'var(--bg-color)',
                color: 'var(--text-color)',
                backdrop: 'rgba(0,0,0,0.7)'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
        });

        // Validación en tiempo real
        const form = document.querySelector('.form-reserva-turno');
        const inputs = form.querySelectorAll('input[required], select[required]');
        
        // Función para validar campos
        function validateField(input) {
            if (!input.checkValidity()) {
                input.style.borderColor = '#e74c3c';
                return false;
            } else {
                input.style.borderColor = '#27ae60';
                return true;
            }
        }

        // Validar al perder el foco
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            
            // Validar mientras se escribe en campos de texto
            if (input.type === 'text' || input.tagName === 'TEXTAREA') {
                input.addEventListener('input', () => validateField(input));
            }
        });

        // Validar formulario al enviar
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar todos los campos
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            // Validar fecha y hora
            const fechaInput = document.getElementById('fecha');
            const horaInput = document.getElementById('hora');
            
            if (fechaInput && horaInput) {
                const fecha = new Date(fechaInput.value);
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                
                if (fecha < hoy) {
                    fechaInput.style.borderColor = '#e74c3c';
                    Swal.fire({
                        title: 'Error',
                        text: 'La fecha no puede ser pasada',
                        icon: 'error',
                        confirmButtonColor: '#027a8d'
                    });
                    isValid = false;
                }
                
                const hora = horaInput.value;
                if (hora < '08:00' || hora > '18:00') {
                    horaInput.style.borderColor = '#e74c3c';
                    Swal.fire({
                        title: 'Error',
                        text: 'El horario de atención es de 8:00 AM a 6:00 PM',
                        icon: 'error',
                        confirmButtonColor: '#027a8d'
                    });
                    isValid = false;
                }
            }

            if (!isValid) {
                event.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor complete todos los campos correctamente',
                    icon: 'error',
                    confirmButtonColor: '#027a8d'
                });
            }
        });

        // Configurar horarios según el tipo de servicio
        const serviceTimeSlots = {
            'vacunacion': 20,   // 20 minutos
            'Control': 20,      // 20 minutos
            'castracion': 60,   // 1 hora
            'baño': 60          // 1 hora
        };

        // Utilidades
        function pad(n){ return String(n).padStart(2,'0'); }

        function buildSlots(interval){
            const slots = [];
            const startHour = 8, endHour = 18; // 8:00 a 18:00
            for (let h = startHour; h < endHour; h++){
                for (let m = 0; m < 60; m += interval){
                    if (h === endHour - 1 && m + interval > 60) break;
                    slots.push(`${pad(h)}:${pad(m)}`);
                }
            }
            return slots;
        }

        // Función para generar los horarios disponibles (ocultando ocupados)
        async function updateTimeSlots() {
            const serviceType = document.getElementById('tipo_servicio').value;
            const dateInput = document.getElementById('fecha');
            const timeInput = document.getElementById('hora');
            const fecha = dateInput.value;

            // Limpiar opciones existentes
            timeInput.innerHTML = '';

            // Estado inicial
            if (!serviceType || !fecha) {
                timeInput.disabled = true;
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Selecciona el horario';
                timeInput.appendChild(opt);
                return;
            }

            timeInput.disabled = false;

            const interval = serviceTimeSlots[serviceType] || 30; // Por defecto 30 minutos
            const allSlots = buildSlots(interval);

            // Obtener horas ocupadas para la fecha y servicio
            let ocupadas = [];
            try {
                const res = await fetch(`api_horas_ocupadas.php?fecha=${encodeURIComponent(fecha)}&tipo_servicio=${encodeURIComponent(serviceType)}`);
                const data = await res.json();
                if (data && data.ok && Array.isArray(data.ocupadas)) {
                    ocupadas = data.ocupadas;
                }
            } catch (err) {
                // Si falla la API, continuamos mostrando todas las horas (el backend igualmente valida)
            }

            // Filtrar horas ocupadas
            const disponibles = allSlots.filter(h => !ocupadas.includes(h));

            // Rellenar opciones
            if (disponibles.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Sin horarios disponibles';
                timeInput.appendChild(opt);
                timeInput.disabled = true;
                return;
            }

            for (const h of disponibles) {
                const option = document.createElement('option');
                option.value = h;
                option.textContent = h;
                timeInput.appendChild(option);
            }

            // Seleccionar el primer horario disponible
            timeInput.value = disponibles[0];
        }

        // Configurar fecha mínima
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('fecha');
            if (fechaInput) {
                const hoy = new Date().toISOString().split('T')[0];
                fechaInput.min = hoy;
                if (!fechaInput.value) {
                    fechaInput.value = hoy;
                }
            }
            
            // Inicializar los horarios
            updateTimeSlots();

            // Actualizar horarios cuando cambie el tipo de servicio o la fecha
            document.getElementById('tipo_servicio').addEventListener('change', updateTimeSlots);
            document.getElementById('fecha').addEventListener('change', updateTimeSlots);
        });
    </script>
</body>
</html>