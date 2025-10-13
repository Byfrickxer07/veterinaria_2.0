<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "veterinaria");
if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

$message = '';
$message_type = '';

$delete_feedback = null;
// Eliminar mascota (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pet'])) {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    if ($pet_id <= 0) {
        $message = 'Mascota inválida.';
        $message_type = 'error';
    } else {
        $stmt = $mysqli->prepare("DELETE FROM mascotas WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $pet_id);
            if ($stmt->execute()) {
                $message = 'Mascota eliminada correctamente.';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar la mascota.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'No se pudo preparar la eliminación.';
            $message_type = 'error';
        }
    }
}

// Editar mascota (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pet'])) {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $nombre = sanitize($_POST['e_nombre'] ?? '');
    $especie = sanitize($_POST['e_especie'] ?? '');
    $raza = sanitize($_POST['e_raza'] ?? '');
    $edad = (int)($_POST['e_edad'] ?? 0);
    $sexo = sanitize($_POST['e_sexo'] ?? '');
    $peso = (float)($_POST['e_peso'] ?? 0);
    $esterilizado = isset($_POST['e_esterilizado']) ? 1 : 0;

    if ($pet_id <= 0) {
        $message = 'Mascota inválida.';
        $message_type = 'error';
    } elseif ($nombre === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u', $nombre)) {
        $message = 'El nombre es requerido y solo puede contener letras y espacios.';
        $message_type = 'error';
    } elseif (!in_array($especie, ['Perro','Gato','Conejo','Ave','Roedor'])) {
        $message = 'Especie inválida.';
        $message_type = 'error';
    } elseif ($edad < 0 || $edad > 30) {
        $message = 'La edad debe estar entre 0 y 30.';
        $message_type = 'error';
    } elseif (!in_array($sexo, ['Macho','Hembra'])) {
        $message = 'Sexo inválido.';
        $message_type = 'error';
    } elseif ($peso <= 0 || $peso > 70) {
        $message = 'El peso debe ser mayor a 0 y hasta 70kg.';
        $message_type = 'error';
    } else {
        $stmt = $mysqli->prepare("UPDATE mascotas SET nombre=?, especie=?, raza=?, edad=?, sexo=?, peso=?, esterilizado=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sssisdii', $nombre, $especie, $raza, $edad, $sexo, $peso, $esterilizado, $pet_id);
            if ($stmt->execute()) {
                $message = 'Mascota actualizada exitosamente.';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar la mascota.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Error preparando la actualización.';
            $message_type = 'error';
        }
    }
}

function sanitize($s) { return trim($s ?? ''); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_pet'])) {
    $owner_id = (int)($_POST['owner_id'] ?? 0);
    $nombre = sanitize($_POST['nombre'] ?? '');
    $especie = sanitize($_POST['especie'] ?? '');
    $raza = sanitize($_POST['raza'] ?? '');
    $edad = (int)($_POST['edad'] ?? 0);
    $sexo = sanitize($_POST['sexo'] ?? '');
    $peso = (float)($_POST['peso'] ?? 0);
    $esterilizado = isset($_POST['esterilizado']) ? 1 : 0;

    if ($owner_id <= 0) {
        $message = 'Debe seleccionar un dueño válido.';
        $message_type = 'error';
    } elseif ($nombre === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u', $nombre)) {
        $message = 'El nombre es requerido y solo puede contener letras y espacios.';
        $message_type = 'error';
    } elseif (!in_array($especie, ['Perro','Gato','Conejo','Ave','Roedor'])) {
        $message = 'Especie inválida.';
        $message_type = 'error';
    } elseif ($edad < 0 || $edad > 30) {
        $message = 'La edad debe estar entre 0 y 30.';
        $message_type = 'error';
    } elseif (!in_array($sexo, ['Macho','Hembra'])) {
        $message = 'Sexo inválido.';
        $message_type = 'error';
    } elseif ($peso <= 0 || $peso > 70) {
        $message = 'El peso debe ser mayor a 0 y hasta 70kg.';
        $message_type = 'error';
    } else {
        $stmt = $mysqli->prepare("INSERT INTO mascotas (user_id, nombre, especie, raza, edad, sexo, peso, esterilizado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('isssisdi', $owner_id, $nombre, $especie, $raza, $edad, $sexo, $peso, $esterilizado);
            if ($stmt->execute()) {
                $message = 'Mascota creada exitosamente.';
                $message_type = 'success';
            } else {
                $message = 'Error al crear la mascota.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Error preparando la creación.';
            $message_type = 'error';
        }
    }
}

// Obtener dueños (clientes)
$clientes = $mysqli->query("SELECT id, nombre_usuario FROM user WHERE rol='cliente' ORDER BY nombre_usuario ASC");

// Filtro de dueño para listado
$filter_owner_id = 0;
if (isset($_GET['filter_owner_id'])) { $filter_owner_id = (int)$_GET['filter_owner_id']; }
if (!$filter_owner_id && isset($_POST['filter_owner_id'])) { $filter_owner_id = (int)$_POST['filter_owner_id']; }
$mascotas_list = null;
if ($filter_owner_id > 0) {
    $stmt = $mysqli->prepare("SELECT id, nombre, especie, raza, edad, sexo, peso, esterilizado FROM mascotas WHERE user_id = ? ORDER BY nombre ASC");
    if ($stmt) {
        $stmt->bind_param('i', $filter_owner_id);
        $stmt->execute();
        $mascotas_list = $stmt->get_result();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Mascotas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            overflow: hidden;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
            background-color: #f4f4f9;
        }
        .dark-mode { background-color:#1c1c1c; color:#e0e0e0; }
        .sidebar {
            width: 275px;
            background-color: #025162;
            color: #ecf0f1;
            padding-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            transition: width 0.3s, background-color 0.3s, box-shadow 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
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
            transition: background-color 0.3s, padding 0.3s, transform 0.15s ease-in-out;
            box-sizing: border-box;
            position: relative;
        }
        .sidebar a:hover { background-color:#03485f; transform: translateY(-1px); }
        .sidebar a.active {
            background-color: #ff6b35;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .sidebar a.active:hover {
            background-color: #e55a2b;
        }
        .sidebar i { margin-right:10px; font-size: 18px; }
        .sidebar span {
            transition: opacity 0.3s ease;
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

        .sidebar .bottom-menu { margin-top:auto; width:100%; padding-bottom:60px; display:flex; flex-direction:column; align-items:center; }
        .content {
            flex-grow: 1;
            margin-left: 275px;
            padding: 40px 30px;
            min-height: 100vh;
            height: 100vh;
            overflow-y: auto;
            padding-bottom: 140px;
        }
        .card {
            background:#fff;
            border-radius:20px;
            box-shadow:0 10px 25px rgba(0,0,0,0.08);
            padding:24px;
            max-width:980px;
            margin:0 auto;
            border: 1px solid #edf2f7;
            margin-bottom: 24px; /* separación entre tarjetas */
        }
        .card h1, .card h2, .card h3 { margin-top: 0; font-weight: 600; color: #025162; }
        .row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .row .full { grid-column:1 / -1; }
        label { display:block; margin:10px 0 6px; font-weight:600; }
        input[type="text"], input[type="number"], select {
            width:100%; padding:12px 14px; border:1px solid #d6dee6; border-radius:12px; box-sizing:border-box;
            transition: border-color .2s ease, box-shadow .2s ease;
            background:#fff;
        }
        input[type="text"]:focus, input[type="number"]:focus, select:focus {
            outline:none; border-color:#027a8d; box-shadow:0 0 0 3px rgba(2,122,141,.15);
        }
        .button { background-color:#027a8d; color:#fff; border:none; padding:10px 16px; border-radius:12px; cursor:pointer; box-shadow:0 4px 10px rgba(2,122,141,0.15); display:inline-flex; align-items:center; gap:8px; }
        .button:hover { background-color:#026a80; transform: translateY(-1px); }
        .button.secondary { background:#eef7f9; color:#025162; box-shadow:none; }
        .button.secondary:hover { background:#e3f3f6; }
        .button.danger { background:#e74949; }
        .button.danger:hover { background:#cc3d3d; }
        .message { padding:12px 16px; border-radius:8px; margin-bottom:16px; }
        .success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        /* Table */
        .table {
            width:100%; border-collapse: separate; border-spacing:0; margin-top:10px; background:#fff;
            border-radius:16px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.06); border:1px solid #edf2f7;
        }
        .table thead th { background:#E3F3F6; color:#2a3c44; font-weight:600; padding:14px; text-align:left; }
        .table tbody td { padding:14px; border-top:1px solid #f0f4f8; }
        .table tbody tr:hover { background:#f9fcfd; }
        .badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .badge.green { background:#e6f7ee; color:#1b7f4d; }
        .badge.gray { background:#eef1f5; color:#475569; }
        .chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; background:#f1f5f9; border-radius:999px; font-size:12px; color:#334155; }
        .chip i { font-size:14px; color:#027a8d; }

        /* Modal base styles */
        .modal { display:none; position:fixed; z-index:10; left:0; top:0; width:100%; height:100%; background-color: rgba(0,0,0,0.45); }
        .modal .modal-content { background:#fff; margin:5% auto; padding:0; border:none; width:90%; max-width:560px; border-radius:18px; overflow:hidden; box-shadow:0 14px 30px rgba(0,0,0,0.18); border:1px solid #eef2f6; }
        .modal .modal-header { padding:16px 20px; background-color:#027a8d; color:#fff; display:flex; justify-content:space-between; align-items:center; }
        .modal .modal-body { padding:20px; }
        .modal .modal-footer { padding:12px 18px; background:#f0f4f8; display:flex; gap:8px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
        </div>
        <a href="admin_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
        <a href="gestionar_usuarios.php"><i class='bx bx-user'></i><span>Gestionar Usuarios</span></a>
        <a href="gestionar_turnos.php"><i class='bx bx-calendar'></i><span>Gestionar Turnos</span></a>
        <a href="gestionar_mascotas.php" class="active"><i class='bx bx-bone'></i><span>Gestionar Mascotas</span></a>
        <a href="adopcion_page.php?view=admin"><i class='bx bx-heart'></i><span>Adopción (Admin)</span></a>
         <div class="bottom-menu">
             <a href="index.php" id="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
         </div>
    </div>
    <div class="content">
        <div class="card">
            <h1>Crear Mascota</h1>
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="row">
                    <div class="full">
                        <label for="owner_id">Dueño (Cliente)</label>
                        <select id="owner_id" name="owner_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php if ($clientes) { while($c = $clientes->fetch_assoc()) { ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre_usuario']) ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div>
                        <label for="nombre">Nombre de la mascota</label>
                        <input type="text" id="nombre" name="nombre" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo letras y espacios">
                    </div>
                    <div>
                        <label for="especie">Especie</label>
                        <select id="especie" name="especie" required>
                            <option value="">Seleccionar</option>
                            <option value="Perro">Perro</option>
                            <option value="Gato">Gato</option>
                            <option value="Conejo">Conejo</option>
                            <option value="Ave">Ave</option>
                            <option value="Roedor">Roedor</option>
                        </select>
                    </div>
                    <div>
                        <label for="raza">Raza</label>
                        <select id="raza" name="raza" required>
                            <option value="" disabled selected>Primero seleccione una especie</option>
                        </select>
                    </div>
                    <div>
                        <label for="edad">Edad (años)</label>
                        <input type="number" id="edad" name="edad" min="0" max="30" step="1" required>
                    </div>
                    <div>
                        <label for="sexo">Sexo</label>
                        <select id="sexo" name="sexo" required>
                            <option value="">Seleccionar</option>
                            <option value="Macho">Macho</option>
                            <option value="Hembra">Hembra</option>
                        </select>
                    </div>
                    <div>
                        <label for="peso">Peso (kg)</label>
                        <input type="number" id="peso" name="peso" min="0.1" max="70" step="0.1" required>
                    </div>
                    <div class="full">
                        <label><input type="checkbox" name="esterilizado" value="1"> Esterilizado</label>
                    </div>
                </div>
                <br>
                <button class="button" type="submit" name="create_pet">Crear Mascota</button>
            </form>
        </div>
        <br>
        <div class="card">
            <h2>Buscar mascotas por dueño</h2>
            <form method="GET" class="row">
                <div class="full">
                    <label for="filter_owner_id">Dueño (Cliente)</label>
                    <select id="filter_owner_id" name="filter_owner_id" onchange="this.form.submit()">
                        <option value="">Seleccione un cliente</option>
                        <?php if ($clientes) { $clientes->data_seek(0); while($c = $clientes->fetch_assoc()) { $sel = ($filter_owner_id == (int)$c['id']) ? 'selected' : ''; ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $sel ?>><?= htmlspecialchars($c['nombre_usuario']) ?></option>
                        <?php } } ?>
                    </select>
                </div>
            </form>

            <?php if ($filter_owner_id > 0): ?>
                <h3>Mascotas del cliente seleccionado</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Especie</th>
                            <th>Raza</th>
                            <th>Edad</th>
                            <th>Sexo</th>
                            <th>Peso (kg)</th>
                            <th>Esterilizado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($mascotas_list && $mascotas_list->num_rows > 0): while($m = $mascotas_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nombre']) ?></td>
                                <td><?= htmlspecialchars($m['especie']) ?></td>
                                <td><?= htmlspecialchars($m['raza']) ?></td>
                                <td><?= (int)$m['edad'] ?></td>
                                <td><span class="badge gray"><?= htmlspecialchars($m['sexo']) ?></span></td>
                                <td><span class="chip"><i class='bx bx-weight'></i><?= htmlspecialchars($m['peso']) ?></span></td>
                                <td>
                                    <?php if ((int)$m['esterilizado'] === 1): ?>
                                        <span class="badge green"><i class='bx bx-check' style="vertical-align:middle;"></i> Sí</span>
                                    <?php else: ?>
                                        <span class="badge gray">No</span>
                                    <?php endif; ?>
                                </td>
                                <td style="display:flex; gap:8px; align-items:center;">
                                    <button class="button" onclick="openEditPetModal(<?= (int)$m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['especie'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['raza'], ENT_QUOTES) ?>', <?= (int)$m['edad'] ?>, '<?= htmlspecialchars($m['sexo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['peso'], ENT_QUOTES) ?>', <?= (int)$m['esterilizado'] ?>)"><i class='bx bx-edit-alt'></i> Editar</button>
                                    <form method="POST" class="delete-pet-form" style="display:inline;">
                                        <input type="hidden" name="pet_id" value="<?= (int)$m['id'] ?>">
                                        <input type="hidden" name="filter_owner_id" value="<?= (int)$filter_owner_id ?>">
                                        <button type="submit" name="delete_pet" class="button danger"><i class='bx bx-trash'></i> Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="8">
                                    <div style="display:flex; align-items:center; gap:10px; color:#64748b; padding:14px;">
                                        <i class='bx bx-info-circle'></i>
                                        <span>No hay mascotas para este cliente.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
        // Razas por especie (igual que en registrar_mascota.php)
        const razasPorEspecie = {
            'Perro': ['Labrador Retriever', 'Pastor Alemán', 'Bulldog', 'Golden Retriever', 'Poodle', 'Beagle', 'Chihuahua', 'Boxer', 'Dálmata', 'Husky Siberiano', 'Sin raza definida'],
            'Gato': ['Siamés', 'Persa', 'Maine Coon', 'Bengalí', 'Esfinge', 'Azul Ruso', 'Angora Turco', 'Ragdoll', 'British Shorthair', 'Siberiano', 'Sin raza definida'],
            'Conejo': ['Holandés Enano', 'Cabeza de León', 'Angora', 'Rex', 'Belier', 'Gigante de Flandes', 'Mini Lop', 'Conejo Enano', 'Sin raza definida'],
            'Ave': ['Periquito', 'Canario', 'Cacatúa', 'Agapornis', 'Loro', 'Ninfa', 'Diamante Mandarín', 'Jilguero', 'Sin raza definida'],
            'Roedor': ['Hámster Sirio', 'Cobaya', 'Conejillo de Indias', 'Ratón Doméstico', 'Rata Doméstica', 'Jerbo', 'Chinchilla', 'Degú', 'Sin raza definida']
        };
        const especieSelect = document.getElementById('especie');
        const razaSelect = document.getElementById('raza');
        function actualizarRazas() {
            const especie = especieSelect.value;
            razaSelect.innerHTML = '';
            if (!especie || !razasPorEspecie[especie]) {
                const def = document.createElement('option');
                def.value = '';
                def.disabled = true;
                def.selected = true;
                def.textContent = 'Primero seleccione una especie';
                razaSelect.appendChild(def);
                return;
            }
            const def = document.createElement('option');
            def.value = '';
            def.disabled = true;
            def.selected = true;
            def.textContent = 'Seleccione una raza...';
            razaSelect.appendChild(def);
            razasPorEspecie[especie].forEach(r => {
                const opt = document.createElement('option');
                opt.value = r;
                opt.textContent = r;
                razaSelect.appendChild(opt);
            });
        }
        especieSelect.addEventListener('change', actualizarRazas);

        // Confirmación al eliminar mascota
        document.querySelectorAll('.delete-pet-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const ok = confirm('¿Seguro que deseas eliminar esta mascota? Esta acción no se puede deshacer.');
                if (!ok) e.preventDefault();
            });
        });

        // Editar mascota: modal + dependencia raza
        window.openEditPetModal = function(id, nombre, especie, raza, edad, sexo, peso, esterilizado) {
            document.getElementById('e_pet_id').value = id;
            document.getElementById('e_nombre').value = nombre;
            const eEspecie = document.getElementById('e_especie');
            const eRaza = document.getElementById('e_raza');
            document.getElementById('e_edad').value = edad;
            document.getElementById('e_sexo').value = sexo;
            document.getElementById('e_peso').value = peso;
            document.getElementById('e_esterilizado').checked = (parseInt(esterilizado) === 1);
            eEspecie.value = especie;
            // Popular razas y seleccionar la actual
            eRaza.innerHTML = '';
            if (razasPorEspecie[especie]) {
                const def = document.createElement('option');
                def.value = '';
                def.disabled = true;
                def.selected = true;
                def.textContent = 'Seleccione una raza...';
                eRaza.appendChild(def);
                razasPorEspecie[especie].forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r; opt.textContent = r; eRaza.appendChild(opt);
                });
                // Seleccionar la raza actual si existe
                setTimeout(() => {
                    for (const opt of eRaza.options) { if (opt.value === raza) { eRaza.value = raza; break; } }
                }, 0);
            } else {
                const opt = document.createElement('option'); opt.value = ''; opt.textContent = 'Primero seleccione una especie'; opt.disabled = true; opt.selected = true; eRaza.appendChild(opt);
            }
            document.getElementById('editPetModal').style.display = 'block';
        }
        window.closeEditPetModal = function() { document.getElementById('editPetModal').style.display = 'none'; }
        document.getElementById('e_especie').addEventListener('change', function(){
            const eRaza = document.getElementById('e_raza');
            eRaza.innerHTML = '';
            const especie = this.value;
            if (!razasPorEspecie[especie]) {
                const def = document.createElement('option'); def.value=''; def.textContent='Primero seleccione una especie'; def.disabled=true; def.selected=true; eRaza.appendChild(def); return;
            }
            const def = document.createElement('option'); def.value=''; def.textContent='Seleccione una raza...'; def.disabled=true; def.selected=true; eRaza.appendChild(def);
             razasPorEspecie[especie].forEach(r => { const opt = document.createElement('option'); opt.value=r; opt.textContent=r; eRaza.appendChild(opt); });
         });

     </script>
    <!-- Modal editar mascota -->
    <div id="editPetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="margin:0;">Editar Mascota</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="pet_id" id="e_pet_id">
                <div class="modal-body">
                    <label for="e_nombre">Nombre</label>
                    <input type="text" id="e_nombre" name="e_nombre" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo letras y espacios">

                    <label for="e_especie">Especie</label>
                    <select id="e_especie" name="e_especie" required>
                        <option value="">Seleccionar</option>
                        <option value="Perro">Perro</option>
                        <option value="Gato">Gato</option>
                        <option value="Conejo">Conejo</option>
                        <option value="Ave">Ave</option>
                        <option value="Roedor">Roedor</option>
                    </select>

                    <label for="e_raza">Raza</label>
                    <select id="e_raza" name="e_raza" required>
                        <option value="" disabled selected>Primero seleccione una especie</option>
                    </select>

                    <label for="e_edad">Edad (años)</label>
                    <input type="number" id="e_edad" name="e_edad" min="0" max="30" step="1" required>

                    <label for="e_sexo">Sexo</label>
                    <select id="e_sexo" name="e_sexo" required>
                        <option value="">Seleccionar</option>
                        <option value="Macho">Macho</option>
                        <option value="Hembra">Hembra</option>
                    </select>

                    <label for="e_peso">Peso (kg)</label>
                    <input type="number" id="e_peso" name="e_peso" min="0.1" max="70" step="0.1" required>

                    <label><input type="checkbox" id="e_esterilizado" name="e_esterilizado" value="1"> Esterilizado</label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeEditPetModal()"><i class='bx bx-x'></i> Cancelar</button>
                    <button type="submit" name="edit_pet" class="button"><i class='bx bx-save'></i> Guardar</button>
                </div>
            </form>
         </div>
     </div>

     <script>
         // Confirmación para cerrar sesión
         document.addEventListener('DOMContentLoaded', function() {
             const logoutButton = document.getElementById('logout-button');
             if (logoutButton) {
                 logoutButton.addEventListener('click', (e) => {
                     e.preventDefault();
                     Swal.fire({
                         title: '¿Estás seguro?',
                         text: '¿Estás seguro de que deseas cerrar sesión?',
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
             }
         });
     </script>
 </body>
 </html>
 <?php $mysqli->close(); ?>
