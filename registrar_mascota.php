<?php
include 'db.php';
session_start();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'registrar_mascota') {
        if (isset($_POST['nombre_mascota'], $_POST['especie'], $_POST['raza'], $_POST['edad'], $_POST['sexo'], $_POST['peso'], $_POST['esterilizado'])) {
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
                $mensaje = "<p>Error: Especie no válida</p>";
                include 'registrar_mascota.php';
                exit();
            }
            $edad = (int)$_POST['edad'];
            $sexo = $_POST['sexo'];
            $peso = (float)$_POST['peso'];
            // Validar que el peso no sea mayor a 70kg
            if ($peso > 70) {
                $mensaje = "<p>Error: El peso no puede ser mayor a 70kg</p>";
                $_POST = array();
                include 'registrar_mascota.php';
                exit();
            }
            $esterilizado = (int)$_POST['esterilizado'];
            $user_id = (int)$_SESSION['user_id'];
            $stored_foto = null;

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $stored_foto = basename($_FILES['foto']['name']);
                $dest_path = $upload_dir . $stored_foto;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest_path)) {
                    $mensaje = "<p>Error al subir la foto.</p>";
                }
            }

            $query = "INSERT INTO mascotas (user_id, nombre, especie, raza, edad, sexo, peso, esterilizado, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("isssisdis", $user_id, $nombre_mascota, $especie, $raza, $edad, $sexo, $peso, $esterilizado, $stored_foto);
                if ($stmt->execute()) {
                    $mensaje = "<p>Mascota registrada con éxito.</p>";
                } else {
                    $mensaje = "<p>Error al registrar: " . htmlspecialchars($stmt->error) . "</p>";
                }
                $stmt->close();
            } else {
                $mensaje = "<p>Error en la consulta: " . htmlspecialchars($conn->error) . "</p>";
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
            overflow-y: auto;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2c3e50;
            min-height: 100vh;
        }

        .dark-mode {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: #ecf0f1;
        }

        .sidebar {
            width: 275px;
            background: linear-gradient(180deg, #025162 0%, #027a8d 100%);
            color: #ecf0f1;
            padding-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(45deg, #027a8d, #03a9c2);
            padding: 12px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(2, 122, 141, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar .toggle-menu:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(2, 122, 141, 0.4);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .sidebar.collapsed .profile-image {
            width: 50px;
            height: 50px;
            border-width: 2px;
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
            padding: 16px 20px;
            width: calc(100% - 30px);
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding: 16px;
            width: 50px;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed a:hover {
            transform: translateX(0) scale(1.1);
        }

        .sidebar i {
            margin-right: 12px;
            font-size: 18px;
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
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
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
            margin-bottom: 35px;
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
            gap: 20px;
            margin-bottom: 20px;
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
            padding: 16px 18px;
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
            padding: 20px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
            min-height: 80px;
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
                width: 75px;
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
                margin: 20px 10px;
                padding: 30px 20px;
            }
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
            <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
        </div>
    </div>
    
    <div class="content">
        <div class="container">
            <div class="form-header">
                <h1><i class='bx bx-plus-circle' style="margin-right: 10px; color: #027a8d;"></i>Registrar Mascota</h1>
                <p>Completa la información de tu nueva mascota</p>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="registrar_mascota">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre_mascota"><i class='bx bx-user' style="margin-right: 5px;"></i>Nombre</label>
                        <input type="text" id="nombre_mascota" name="nombre_mascota" 
                               pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" 
                               title="Solo se permiten letras y espacios" 
                               placeholder="Ej: Max" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="especie"><i class='bx bx-category' style="margin-right: 5px;"></i>Especie</label>
                        <select id="especie" name="especie" required onchange="actualizarRazas()">
                            <option value="" disabled selected>Seleccionar </option>
                            <option value="Perro">Perro</option>
                            <option value="Gato">Gato</option>
                            <option value="Conejo">Conejo</option>
                            <option value="Ave">Ave</option>
                            <option value="Roedor">Roedor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="raza"><i class='bx bx-dna' style="margin-right: 5px;"></i>Raza</label>
                        <select id="raza" name="raza" required>
                            <option value="" disabled selected>Primero seleccione una especie</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edad"><i class='bx bx-time' style="margin-right: 5px;"></i>Edad (años)</label>
                        <input type="number" id="edad" name="edad" min="0" max="30" step="1" placeholder="Ej: 3" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sexo"><i class='bx bx-male-sign' style="margin-right: 5px;"></i>Sexo</label>
                        <select id="sexo" name="sexo" required>
                            <option value="" disabled selected>Seleccionar...</option>
                            <option value="Macho">Macho</option>
                            <option value="Hembra">Hembra</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="peso"><i class='bx bx-chart' style="margin-right: 5px;"></i>Peso (kg) <span id="peso-error" style="color: red; font-size: 0.8em; display: none;">(Máximo 70kg)</span></label>
                        <input type="number" id="peso" name="peso" min="0.1" max="70" step="0.1" placeholder="Ej: 25.5" required
                               oninput="validarPeso(this)">
                    </div>
                    
                    <div class="form-group">
                        <label for="esterilizado"><i class='bx bx-health' style="margin-right: 5px;"></i>Esterilizado</label>
                        <select id="esterilizado" name="esterilizado" required>
                            <option value="" disabled selected>Seleccionar...</option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
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
            function validarPeso(input) {
                const errorSpan = document.getElementById('peso-error');
                if (parseFloat(input.value) > 70) {
                    input.setCustomValidity('El peso no puede ser mayor a 70kg');
                    errorSpan.style.display = 'inline';
                } else {
                    input.setCustomValidity('');
                    errorSpan.style.display = 'none';
                }
            }
            
            // Validar el formulario antes de enviar
            document.querySelector('form').addEventListener('submit', function(e) {
                const pesoInput = document.getElementById('peso');
                if (parseFloat(pesoInput.value) > 70) {
                    e.preventDefault();
                    alert('El peso no puede ser mayor a 70kg');
                    pesoInput.focus();
                }
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
            document.getElementById('nombre_mascota').addEventListener('input', function(e) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑ\s]/g, '');
            });
            </script>
            
            <?php if ($mensaje) : ?>
                <div class="message <?= strpos($mensaje, 'Error') !== false ? 'error' : 'success' ?>">
                    <div class="icon-wrapper">
                        <i class='bx <?= strpos($mensaje, 'Error') !== false ? 'bx-error' : 'bx-check' ?>'></i>
                    </div>
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