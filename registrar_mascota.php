<?php
include 'db.php';
session_start();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'registrar_mascota') {
        if (isset($_POST['nombre_mascota'], $_POST['especie'], $_POST['raza'], $_POST['edad'], $_POST['sexo'], $_POST['peso'], $_POST['esterilizado']) &&
            !empty($_POST['nombre_mascota']) && !empty($_POST['especie']) && !empty($_POST['raza']) && 
            !empty($_POST['edad']) && !empty($_POST['sexo']) && !empty($_POST['peso']) && 
            ($_POST['esterilizado'] === '0' || !empty($_POST['esterilizado']))) {
            // La foto es opcional, no se valida aquí
            if (!isset($_SESSION['user_id'])) {
                header('Location: login.php');
                exit();
            }
            $nombre_mascota = trim($_POST['nombre_mascota']);
            $especie = trim($_POST['especie']);
            $raza = trim($_POST['raza']);
            
            // Validar que la especie sea una de las permitidas
            $especies_permitidas = ['Perro', 'Gato', 'Conejo', 'Ave', 'Roedor'];
            if (!in_array($especie, $especies_permitidas)) {
                $mensaje = "error:La especie seleccionada no es válida. Por favor, elige una especie de la lista.";
                include 'registrar_mascota.php';
                exit();
            }
            $edad = (int)$_POST['edad'];
            $sexo = $_POST['sexo'];
            $peso = (float)$_POST['peso'];
            // Validar que el peso no sea mayor a 70kg
            if ($peso > 70) {
                $mensaje = "error:El peso de la mascota no puede ser mayor a 70kg. Por favor, ingresa un peso válido.";
                $_POST = array();
                include 'registrar_mascota.php';
                exit();
            }
            $esterilizado = (int)$_POST['esterilizado'];
            $user_id = (int)$_SESSION['user_id'];
            
            // La foto es completamente opcional
            $stored_foto = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto']['name'])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $stored_foto = basename($_FILES['foto']['name']);
                $dest_path = $upload_dir . $stored_foto;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest_path)) {
                    $mensaje = "error:No se pudo subir la foto de la mascota. Por favor, intenta con otra imagen.";
                }
            }

            $query = "INSERT INTO mascotas (user_id, nombre, especie, raza, edad, sexo, peso, esterilizado, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("isssisdis", $user_id, $nombre_mascota, $especie, $raza, $edad, $sexo, $peso, $esterilizado, $stored_foto);
                if ($stmt->execute()) {
                    // Para AJAX, solo devolver éxito
                    echo "success";
                    exit();
                } else {
                    echo "error:Error al registrar la mascota: " . htmlspecialchars($stmt->error);
                    exit();
                }
                $stmt->close();
            } else {
                echo "error:Error en la base de datos: " . htmlspecialchars($conn->error);
                exit();
            }
        } else {
            echo "error:Por favor, completa todos los campos requeridos.";
            exit();
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            overflow: hidden; /* Igual que sacar_turno: evitamos scroll horizontal bajo el menú */
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2c3e50;
            min-height: 100vh;
        }
        .dark-mode {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: #ecf0f1;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #025162 0%, #034854 100%);
            backdrop-filter: blur(10px);
            color: #ecf0f1;
            padding-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar.collapsed .user-name,
        .sidebar.collapsed span {
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.2s ease;
            display: inline-block;
            width: 0;
            overflow: hidden;
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
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar .toggle-menu:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(2, 122, 141, 0.4);
        }

        .sidebar.collapsed .toggle-menu {
            right: -18px;
            transform: rotate(180deg);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 40px;
            transition: all 0.3s ease;
            width: 100%;
            padding: 0 20px;
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

        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            opacity: 0.95;
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
            font-size: 20px;
        }

        .sidebar .bottom-menu {
            margin-top: auto;
            width: 100%;
            padding-bottom: 40px; /* Aumentado de 20px a 40px */
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px; /* Añadido espacio entre botones */
        }

        .content {
            flex-grow: 1;
            padding: 40px 40px 20px 40px; /* Reducido padding inferior */
            display: flex;
            justify-content: center;
            align-items: center; /* Cambiado de flex-start a center para centrar verticalmente */
            height: 100vh; /* Altura fija para limitar el scroll */
            margin-left: 280px;
            overflow: hidden; /* Quitado el scroll completamente */
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .sidebar.collapsed ~ .content {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 25px; /* Reducido aún más */
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: visible; /* sin scroll interno, lo maneja .content */
            margin-bottom: 10px; /* Reducido margen inferior */
        }

        .container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(2, 122, 141, 0.05), transparent);
            transform: rotate(45deg);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .container:hover::before {
            opacity: 1;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }

        .form-header {
            text-align: center;
            margin-bottom: 25px; /* Reducido de 35px a 25px */
            position: relative;
            z-index: 2;
        }

        .form-header h1 {
            color: #025162;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .form-header p {
            color: #64748b;
            font-size: 16px;
            font-weight: 400;
            margin: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px; /* Reducido de 20px a 15px */
            margin-bottom: 15px; /* Reducido de 20px a 15px */
        }

        .form-group {
            position: relative;
            z-index: 2;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 14px; /* Reducido de 16px 18px a 12px 14px */
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 400;
            background: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #027a8d;
            box-shadow: 0 0 0 3px rgba(2, 122, 141, 0.1);
            background: #ffffff;
            transform: translateY(-2px);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-display {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px; /* Reducido de 20px a 15px */
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
            min-height: 60px; /* Reducido de 80px a 60px */
            text-align: center;
        }

        .file-input-wrapper:hover .file-input-display {
            border-color: #027a8d;
            background: #f0fdff;
        }

        .file-input-display i {
            font-size: 24px;
            color: #64748b;
            margin-right: 10px;
        }

        .file-input-display span {
            color: #64748b;
            font-weight: 500;
        }

        .submit-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #025162 0%, #027a8d 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(2, 122, 141, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            z-index: 2;
            overflow: hidden;
        }

        .submit-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-button:hover::before {
            left: 100%;
        }

        .submit-button:hover {
            background: linear-gradient(135deg, #014751 0%, #02677a 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 122, 141, 0.4);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .message {
            margin-top: 25px;
            padding: 16px 20px;
            border-radius: 12px;
            font-weight: 500;
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #bbf7d0;
        }

        .message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
        }

        /* Estilos para mensajes de error personalizados */
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: none;
            font-weight: 500;
        }

        .error-message.show {
            display: block;
        }

        .form-group.error input,
        .form-group.error select {
            border-color: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
        }

        /* Dark mode styles */
        .dark-mode .container {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .dark-mode .form-header h1 {
            color: #e2e8f0;
        }

        .dark-mode .form-header p {
            color: #94a3b8;
        }

        .dark-mode label {
            color: #e2e8f0;
        }

        .dark-mode input[type="text"],
        .dark-mode input[type="number"],
        .dark-mode select {
            background: rgba(15, 23, 42, 0.8);
            border-color: #475569;
            color: #e2e8f0;
        }

        .dark-mode .file-input-display {
            background: rgba(15, 23, 42, 0.8);
            border-color: #475569;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px; /* igual que en sacar_turno */
            }
            
            .sidebar .user-name,
            .sidebar span {
                display: none;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .container {
                margin: 20px 10px 10px 10px; /* Reducido margen inferior */
                padding: 25px 20px; /* Reducido padding */
            }

            .content {
                margin-left: 70px; /* coincide con el ancho móvil del menú */
                padding: 15px 10px 5px 10px; /* Reducido aún más */
                height: 100vh; /* Altura fija también en móvil */
                overflow: hidden; /* Quitado el scroll también en móvil */
                align-items: center; /* Centrar verticalmente en móvil */
            }
        }

        /* Desactivar colapso del sidebar en esta página */
        .sidebar .toggle-menu { display: none !important; }
        .sidebar.collapsed { width: 280px !important; }
        .sidebar.collapsed .user-name,
        .sidebar.collapsed span { display: inline !important; opacity: 1 !important; transform: none !important; }
        .sidebar.collapsed ~ .content { margin-left: 280px !important; }
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
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
        </div>
        <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
        <a href="sacar_turno.php"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
        <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
        <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
        <a href="registrar_mascota.php" class="active"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
        <a href="historial_cliente.php"><i class='bx bx-notepad'></i><span>Historial Clínico</span></a>
        <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>
        <div class="bottom-menu">
            <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>
    
    <div class="content">
        <div class="container">
            <div class="form-header">
                <h1><i class='bx bx-plus-circle' style="margin-right: 10px; color: #027a8d;"></i>Registrar Mascota</h1>
                <p>Completa la información de tu nueva mascota</p>
            </div>
            
             <form id="registro-form" enctype="multipart/form-data" novalidate>
                 <input type="hidden" name="action" value="registrar_mascota">
                
                <div class="form-grid">
                     <div class="form-group">
                         <label for="nombre_mascota"><i class='bx bx-user' style="margin-right: 5px;"></i>Nombre</label>
                         <input type="text" id="nombre_mascota" name="nombre_mascota" 
                                placeholder="Ej: Max"
                                value="<?php echo isset($_POST['nombre_mascota']) ? htmlspecialchars($_POST['nombre_mascota']) : ''; ?>">
                         <div class="error-message" id="error-nombre_mascota">Por favor, ingresa el nombre de la mascota</div>
                     </div>
                    
                     <div class="form-group">
                         <label for="especie"><i class='bx bx-category' style="margin-right: 5px;"></i>Especie</label>
                         <select id="especie" name="especie" onchange="actualizarRazas()">
                             <option value="" disabled <?php echo !isset($_POST['especie']) ? 'selected' : ''; ?>>Seleccionar </option>
                             <option value="Perro" <?php echo (isset($_POST['especie']) && $_POST['especie'] == 'Perro') ? 'selected' : ''; ?>>Perro</option>
                             <option value="Gato" <?php echo (isset($_POST['especie']) && $_POST['especie'] == 'Gato') ? 'selected' : ''; ?>>Gato</option>
                             <option value="Conejo" <?php echo (isset($_POST['especie']) && $_POST['especie'] == 'Conejo') ? 'selected' : ''; ?>>Conejo</option>
                             <option value="Ave" <?php echo (isset($_POST['especie']) && $_POST['especie'] == 'Ave') ? 'selected' : ''; ?>>Ave</option>
                             <option value="Roedor" <?php echo (isset($_POST['especie']) && $_POST['especie'] == 'Roedor') ? 'selected' : ''; ?>>Roedor</option>
                         </select>
                         <div class="error-message" id="error-especie">Por favor, selecciona una especie</div>
                     </div>
                    
                     <div class="form-group">
                         <label for="raza"><i class='bx bx-dna' style="margin-right: 5px;"></i>Raza</label>
                         <select id="raza" name="raza">
                             <option value="" disabled selected>Primero seleccione una especie</option>
                         </select>
                         <div class="error-message" id="error-raza">Por favor, selecciona una raza</div>
                     </div>
                    
                     <div class="form-group">
                         <label for="edad"><i class='bx bx-time' style="margin-right: 5px;"></i>Edad (años)</label>
                         <input type="number" id="edad" name="edad" placeholder="Ej: 3"
                                value="<?php echo isset($_POST['edad']) ? htmlspecialchars($_POST['edad']) : ''; ?>">
                         <div class="error-message" id="error-edad">Por favor, ingresa la edad de la mascota</div>
                     </div>
                    
                     <div class="form-group">
                         <label for="sexo"><i class='bx bx-male-sign' style="margin-right: 5px;"></i>Sexo</label>
                         <select id="sexo" name="sexo">
                             <option value="" disabled <?php echo !isset($_POST['sexo']) ? 'selected' : ''; ?>>Seleccionar...</option>
                             <option value="Macho" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'Macho') ? 'selected' : ''; ?>>Macho</option>
                             <option value="Hembra" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'Hembra') ? 'selected' : ''; ?>>Hembra</option>
                         </select>
                         <div class="error-message" id="error-sexo">Por favor, selecciona el sexo de la mascota</div>
                     </div>
                    
                     <div class="form-group">
                         <label for="peso"><i class='bx bx-chart' style="margin-right: 5px;"></i>Peso (kg)</label>
                         <input type="number" id="peso" name="peso" placeholder="Ej: 25.5"
                                value="<?php echo isset($_POST['peso']) ? htmlspecialchars($_POST['peso']) : ''; ?>"
                                oninput="validarPeso(this)">
                         <div class="error-message" id="error-peso">Por favor, ingresa el peso de la mascota</div>
                     </div>
                    
                    
                     <div class="form-group">
                         <label for="esterilizado"><i class='bx bx-check-shield' style="margin-right: 5px;"></i>Estado de esterilización</label>
                         <select id="esterilizado" name="esterilizado">
                             <option value="" disabled <?php echo !isset($_POST['esterilizado']) ? 'selected' : ''; ?>>Seleccionar...</option>
                             <option value="1" <?php echo (isset($_POST['esterilizado']) && $_POST['esterilizado'] == '1') ? 'selected' : ''; ?>>Sí</option>
                             <option value="0" <?php echo (isset($_POST['esterilizado']) && $_POST['esterilizado'] == '0') ? 'selected' : ''; ?>>No</option>
                         </select>
                         <div class="error-message" id="error-esterilizado">Por favor, selecciona el estado de esterilización</div>
                     </div>
                    
                    <div class="form-group full-width">
                        <label for="foto"><i class='bx bx-image' style="margin-right: 5px;"></i>Foto de la Mascota</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="foto" name="foto" accept="image/*">
                            <div class="file-input-display">
                                <i class='bx bx-cloud-upload'></i>
                                <span>Haz clic para seleccionar una imagen</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-button">
                    <i class='bx bx-save' style="margin-right: 8px;"></i>Registrar Mascota
                </button>
            </form>
            
             <script>
             // Función para mostrar/ocultar errores
             function showError(fieldId, message) {
                 const field = document.getElementById(fieldId);
                 if (!field) return;
                 
                 const errorDiv = document.getElementById('error-' + fieldId);
                 const formGroup = field.closest('.form-group');
                 
                 if (errorDiv) {
                     errorDiv.textContent = message;
                     errorDiv.classList.add('show');
                 }
                 if (formGroup) {
                     formGroup.classList.add('error');
                 }
             }
             
             function hideError(fieldId) {
                 const field = document.getElementById(fieldId);
                 if (!field) return;
                 
                 const errorDiv = document.getElementById('error-' + fieldId);
                 const formGroup = field.closest('.form-group');
                 
                 if (errorDiv) {
                     errorDiv.classList.remove('show');
                 }
                 if (formGroup) {
                     formGroup.classList.remove('error');
                 }
             }
             
             function validarPeso(input) {
                 const peso = parseFloat(input.value);
                 if (peso > 70) {
                     showError('peso', 'El peso no puede ser mayor a 70kg');
                     return false;
                 } else {
                     hideError('peso');
                     return true;
                 }
             }
             
             // Validación en tiempo real
             document.addEventListener('DOMContentLoaded', function() {
                 const form = document.querySelector('form');
                 const requiredFields = [
                     { id: 'nombre_mascota', message: 'Por favor, ingresa el nombre de la mascota' },
                     { id: 'especie', message: 'Por favor, selecciona una especie' },
                     { id: 'raza', message: 'Por favor, selecciona una raza' },
                     { id: 'edad', message: 'Por favor, ingresa la edad de la mascota' },
                     { id: 'sexo', message: 'Por favor, selecciona el sexo de la mascota' },
                     { id: 'peso', message: 'Por favor, ingresa el peso de la mascota' },
                     { id: 'esterilizado', message: 'Por favor, selecciona el estado de esterilización' }
                     // La foto NO es obligatoria, por eso no está en la lista
                 ];
                 
                 // Agregar validación en tiempo real
                 requiredFields.forEach(field => {
                     const element = document.getElementById(field.id);
                     if (element) {
                         element.addEventListener('blur', function() {
                             if (!this.value || this.value.trim() === '') {
                                 showError(field.id, field.message);
                             } else {
                                 hideError(field.id);
                             }
                         });
                         
                         element.addEventListener('input', function() {
                             if (this.value && this.value.trim() !== '') {
                                 hideError(field.id);
                             }
                         });
                     }
                 });
                 
                 // Validación especial para peso
                 const pesoInput = document.getElementById('peso');
                 if (pesoInput) {
                     pesoInput.addEventListener('input', function() {
                         if (this.value) {
                             validarPeso(this);
                         } else {
                             hideError('peso');
                         }
                     });
                 }
                 
                 // Envío del formulario con AJAX
                 form.addEventListener('submit', function(e) {
                     e.preventDefault(); // Prevenir envío normal
                     
                     let hasErrors = false;
                     
                     // Limpiar errores anteriores
                     requiredFields.forEach(field => {
                         hideError(field.id);
                     });
                     
                     // Validar todos los campos requeridos
                     requiredFields.forEach(field => {
                         const element = document.getElementById(field.id);
                         console.log('Validando campo:', field.id, 'Valor:', element ? element.value : 'No encontrado'); // Debug
                         if (element && (!element.value || element.value.trim() === '')) {
                             console.log('Mostrando error para:', field.id); // Debug
                             showError(field.id, field.message);
                             hasErrors = true;
                         }
                     });
                     
                     // Validación especial para peso
                     const pesoInput = document.getElementById('peso');
                     if (pesoInput && pesoInput.value) {
                         if (!validarPeso(pesoInput)) {
                             hasErrors = true;
                         }
                     }
                     
                     if (hasErrors) {
                         return false;
                     }
                     
                     // Enviar con AJAX
                     const formData = new FormData(form);
                     
                     fetch('', {
                         method: 'POST',
                         body: formData
                     })
                     .then(response => response.text())
                     .then(data => {
                         console.log('Respuesta del servidor:', data); // Debug
                         if (data.trim() === 'success') {
                             // Limpiar todos los campos
                             document.getElementById('nombre_mascota').value = '';
                             document.getElementById('especie').selectedIndex = 0;
                             document.getElementById('raza').innerHTML = '<option value="" disabled selected>Primero seleccione una especie</option>';
                             document.getElementById('edad').value = '';
                             document.getElementById('sexo').selectedIndex = 0;
                             document.getElementById('peso').value = '';
                             document.getElementById('esterilizado').selectedIndex = 0;
                             document.getElementById('foto').value = '';
                             
                             // Mostrar mensaje de éxito
                             Swal.fire({
                                 title: '¡Éxito!',
                                 text: '¡Mascota registrada con éxito!',
                                 icon: 'success',
                                 confirmButtonColor: '#027a8d',
                                 confirmButtonText: 'Aceptar',
                                 background: '#fff',
                                 customClass: {
                                     popup: 'animated fadeIn'
                                 }
                             });
                         } else {
                             // Mostrar error
                             Swal.fire({
                                 title: 'Error',
                                 text: data.replace('error:', ''),
                                 icon: 'error',
                                 confirmButtonColor: '#dc3545',
                                 confirmButtonText: 'Aceptar',
                                 background: '#fff'
                             });
                         }
                     })
                     .catch(error => {
                         Swal.fire({
                             title: 'Error',
                             text: 'Error al registrar la mascota. Por favor, intenta nuevamente.',
                             icon: 'error',
                             confirmButtonColor: '#dc3545',
                             confirmButtonText: 'Aceptar',
                             background: '#fff'
                         });
                     });
                 });
             });
             </script>
            
            <script>
            // Datos de razas por especie
            const razasPorEspecie = {
                'Perro': ['Labrador Retriever', 'Pastor Alemán', 'Bulldog', 'Golden Retriever', 'Poodle', 'Beagle', 'Chihuahua', 'Boxer', 'Dálmata', 'Husky Siberiano', 'Sin raza definida'],
                'Gato': ['Siamés', 'Persa', 'Maine Coon', 'Bengalí', 'Esfinge', 'Azul Ruso', 'Angora Turco', 'Ragdoll', 'British Shorthair', 'Siberiano', 'Sin raza definida'],
                'Conejo': ['Holandés Enano', 'Cabeza de León', 'Angora', 'Rex', 'Belier', 'Gigante de Flandes', 'Mini Lop', 'Conejo Enano', 'Sin raza definida'],
                'Ave': ['Periquito', 'Canario', 'Cacatúa', 'Agapornis', 'Loro', 'Ninfa', 'Diamante Mandarín', 'Jilguero', 'Sin raza definida'],
                'Roedor': ['Hámster Sirio', 'Cobaya', 'Conejillo de Indias', 'Ratón Doméstico', 'Rata Doméstica', 'Jerbo', 'Chinchilla', 'Degú', 'Sin raza definida']
            };

            function actualizarRazas() {
                const especieSelect = document.getElementById('especie');
                const razaSelect = document.getElementById('raza');
                const especieSeleccionada = especieSelect.value;
                
                // Limpiar opciones actuales
                razaSelect.innerHTML = '';
                
                if (!especieSeleccionada) {
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Primero seleccione una especie';
                    defaultOption.disabled = true;
                    defaultOption.selected = true;
                    razaSelect.appendChild(defaultOption);
                    return;
                }
                
                // Agregar opción predeterminada
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Seleccione una raza...';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                razaSelect.appendChild(defaultOption);
                
                // Agregar razas correspondientes
                razasPorEspecie[especieSeleccionada].forEach(raza => {
                    const option = document.createElement('option');
                    option.value = raza;
                    option.textContent = raza;
                    razaSelect.appendChild(option);
                });
            }

            // Validar que solo se ingresen letras en el nombre
            // Validación del nombre para solo letras
            document.getElementById('nombre_mascota').addEventListener('input', function(e) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑ\s]/g, '');
            });
            
            // Validación de edad para solo números positivos
            document.getElementById('edad').addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^0-9]/g, '');
                if (value > 30) value = 30;
                e.target.value = value;
            });
            </script>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="alertas_clientes.js"></script>
    <script>
        // Ya no necesitamos manejar mensajes PHP porque usamos AJAX
    </script>
    <script>
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
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                background: '#fff',
                customClass: {
                    popup: 'animated fadeIn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
        });

        // Mejorar la experiencia del input de archivo
        const fileInput = document.getElementById('foto');
        const fileDisplay = document.querySelector('.file-input-display span');
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                fileDisplay.textContent = `Archivo seleccionado: ${e.target.files[0].name}`;
                fileDisplay.style.color = '#027a8d';
            } else {
                fileDisplay.textContent = 'Haz clic para seleccionar una imagen';
                fileDisplay.style.color = '#64748b';
            }
        });

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>

