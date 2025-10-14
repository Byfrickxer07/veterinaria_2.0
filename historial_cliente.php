<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    header("Location: login.php");
    exit();
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "veterinaria";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }
$userId = (int)$_SESSION['user_id'];
$mascotas = [];
$stmt = $conn->prepare("SELECT id, nombre, especie, raza, foto FROM mascotas WHERE user_id = ? ORDER BY nombre ASC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $mascotas[] = $row; }
$stmt->close();
$historial = [];
$mascotaSeleccionada = null;
$mascotaId = isset($_GET['mascota_id']) ? (int)$_GET['mascota_id'] : 0;
if ($mascotaId > 0) {
    $stmtPet = $conn->prepare("SELECT id, nombre FROM mascotas WHERE id = ? AND user_id = ?");
    $stmtPet->bind_param('ii', $mascotaId, $userId);
    $stmtPet->execute();
    $petRes = $stmtPet->get_result();
    if ($petRes->num_rows > 0) { $mascotaSeleccionada = $petRes->fetch_assoc(); }
    $stmtPet->close();
    if ($mascotaSeleccionada) {
        $stmtH = $conn->prepare("SELECT fecha_consulta, motivo_consulta, diagnostico, procedimientos_realizados, historial_vacunacion, alergias, medicamentos_actuales FROM historial_clinico WHERE mascota_id = ? ORDER BY fecha_consulta DESC");
        $stmtH->bind_param('i', $mascotaId);
        $stmtH->execute();
        $hRes = $stmtH->get_result();
        while ($row = $hRes->fetch_assoc()) { $historial[] = $row; }
        $stmtH->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial Clínico</title>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
 body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #f4f4f9; color: #2c3e50; }
 .sidebar { width: 275px; background: #025162; color: #ecf0f1; padding-top: 40px; display:flex; flex-direction:column; align-items:center; height:100vh; position: fixed; top:0; left:0; box-shadow: 4px 0 20px rgba(0,0,0,0.1); }
 .sidebar .profile-section{ text-align:center; margin-bottom: 20px; }
 .sidebar .profile-image{ width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid rgba(255,255,255,0.2); }
 .sidebar a{ display:flex; align-items:center; color:#ecf0f1; text-decoration:none; padding:14px 20px; width: calc(100% - 40px); margin: 0 20px 12px 20px; background:#027a8d; border-radius:12px; transition:.2s; }
 .sidebar a i{ margin-right:10px; font-size:18px; }
 .sidebar a:hover{ background:#03485f; transform: translateY(-1px); }
 .sidebar a.active{ background:#ff6b35; border:2px solid rgba(255,255,255,0.2); }
 .bottom-menu{ margin-top:auto; width:100%; padding-bottom:20px; display:flex; flex-direction:column; align-items:center; }
 .content{ margin-left:275px; padding:30px; }
 h1{ color:#025162; margin-bottom: 20px; }
 .pets-grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap:16px; }
 .pet-card{ background:#fff; border:1px solid #e6e6e6; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; flex-direction:column; gap:10px; }
 .pet-header{ display:flex; align-items:center; gap:12px; }
 .pet-avatar{ width:48px; height:48px; border-radius:50%; object-fit:cover; background:#eaf4f6; display:flex; align-items:center; justify-content:center; color:#025162; }
 .btn{ display:inline-flex; align-items:center; gap:8px; padding:10px 12px; background:#025162; color:#fff; text-decoration:none; border-radius:10px; border:none; cursor:pointer; transition:.2s; font-size:14px; }
 .btn:hover{ background:#03485f; }
 .history-section{ margin-top:12px; display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:16px; align-items:start; }
 .historial-item{ background:#fff; border:1px solid #e6e6e6; border-radius:12px; padding:12px 14px; }
 .historial-item h3{ color:#025162; margin:0 0 6px 0; }
 .historial-item div{ line-height:1.35; margin-bottom:4px; }
 .history-title{ margin-top:16px; margin-bottom:8px; color:#025162; }
 .history-section .empty{ grid-column: 1 / -1; }
 .empty{ background:#fff; border:1px dashed #cfd8dc; color:#607d8b; padding:16px; border-radius:12px; }
 @media(max-width: 992px){ .content{ margin-left:0; } .sidebar{ position: static; height:auto; width:100%; } }
</style>
    <style>
        /* Override para igualar el diseño del sidebar de la imagen */
        .sidebar { 
            width: 275px; 
            background: linear-gradient(180deg, #025162 0%, #034854 100%);
            padding-top: 40px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.15); 
        }
        .profile-section { margin-bottom: 24px; }
        .sidebar .profile-image { 
            width: 110px; height: 110px; border-radius: 50%; 
            border: 3px solid rgba(255,255,255,0.85);
            box-shadow: 0 6px 16px rgba(0,0,0,0.25);
        }
        .sidebar a { 
            background: linear-gradient(180deg, #10919c 0%, #0d7680 100%);
            padding: 16px 20px; 
            width: calc(100% - 40px); 
            margin: 0 20px 18px; 
            border-radius: 18px; 
            font-size: 16px; 
            font-weight: 600;
            color: #eef9fb; 
            box-sizing: border-box;
            display: flex; align-items: center;
            box-shadow: 0 10px 16px rgba(0,0,0,0.16);
            border: 1px solid rgba(255,255,255,0.10);
        }
        .sidebar a i { margin-right: 12px; font-size: 18px; }
        .sidebar a:hover { filter: brightness(0.98); transform: translateY(-1px); }
        .sidebar a.active { 
            background: linear-gradient(180deg, #ff8652 0%, #ff6b35 100%);
            color: #ffffff;
            box-shadow: 0 12px 22px rgba(255, 107, 53, 0.45); 
            border: 1px solid rgba(255, 255, 255, 0.20); 
        }
        .bottom-menu { margin-top: auto; width: 100%; padding: 12px 0 24px; margin-bottom: 72px; }
        .logout-button { 
            display: block; 
            width: calc(100% - 40px); 
            margin: 0 20px; 
            box-sizing: border-box;
            background: linear-gradient(180deg, #10919c 0%, #0d7680 100%);
            color: #ff6b6b !important; 
            text-align: center; 
            padding: 14px 18px; 
            border-radius: 18px; 
            box-shadow: 0 10px 16px rgba(0,0,0,0.16);
            border: 1px solid rgba(255,255,255,0.10);
        }
        .logout-button i { color: #ff6b6b !important; }
        .logout-button:hover { filter: brightness(0.98); }
        .content { margin-left: 275px; }
    </style>
</head>
<body>
<!-- Botón de menú móvil -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
  <i class='bx bx-menu'></i>
 </button>
<div class="sidebar" id="sidebar">
  <div class="profile-section">
    <img src="logo_perro.jpg" class="profile-image" alt="Usuario">
  </div>
  <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
  <a href="sacar_turno.php"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
  <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
  <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
  <a href="registrar_mascota.php"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
  <a href="historial_cliente.php" class="active"><i class='bx bx-notepad'></i><span>Historial Clínico</span></a>
  <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>
  <div class="bottom-menu">
    <a href="index.php" id="logout-button" class="logout-button"><i class='bx bx-log-out'></i><span>Cerrar Sesión</span></a>
  </div>
</div>
<div class="content">
  <h1 style="text-align: center;">Historial clínico</h1>
  <div class="pets-grid">
    <?php if (count($mascotas) === 0): ?>
      <div class="empty">No tenés mascotas registradas.</div>
    <?php else: foreach ($mascotas as $m): ?>
      <div class="pet-card">
        <div class="pet-header">
          <?php if (!empty($m['foto'])): ?>
            <img class="pet-avatar" src="<?php echo htmlspecialchars($m['foto']); ?>" alt="<?php echo htmlspecialchars($m['nombre']); ?>">
          <?php else: ?>
            <div class="pet-avatar"><i class='bx bx-paw'></i></div>
          <?php endif; ?>
          <div>
            <div><strong><?php echo htmlspecialchars($m['nombre']); ?></strong></div>
            <div style="font-size:12px;color:#607d8b;"><?php echo htmlspecialchars($m['especie'] . ' · ' . $m['raza']); ?></div>
          </div>
        </div>
        <a class="btn" href="historial_cliente.php?mascota_id=<?php echo (int)$m['id']; ?>"><i class='bx bx-show'></i>Ver historial</a>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <?php if ($mascotaSeleccionada): ?>
    <h2 style="text-align: center;" class="history-title">Historial de <?php echo htmlspecialchars($mascotaSeleccionada['nombre']); ?></h2>
    <div class="history-section">
      <?php if (count($historial) === 0): ?>
        <div class="empty">Todavía no tiene historial.</div>
      <?php else: foreach ($historial as $h): ?>
        <div class="historial-item">
          <h3>Fecha de consulta: <?php echo htmlspecialchars($h['fecha_consulta']); ?></h3>
          <div><strong>Motivo de consulta:</strong> <?php echo htmlspecialchars($h['motivo_consulta']); ?></div>
          <?php if (!empty($h['diagnostico'])): ?><div><strong>Diagnóstico:</strong> <?php echo htmlspecialchars($h['diagnostico']); ?></div><?php endif; ?>
          <?php if (!empty($h['procedimientos_realizados'])): ?><div><strong>Procedimientos realizados:</strong> <?php echo htmlspecialchars($h['procedimientos_realizados']); ?></div><?php endif; ?>
          <?php if (!empty($h['historial_vacunacion'])): ?><div><strong>Historial de vacunación:</strong> <?php echo htmlspecialchars($h['historial_vacunacion']); ?></div><?php endif; ?>
          <?php if (!empty($h['alergias'])): ?><div><strong>Alergias:</strong> <?php echo htmlspecialchars($h['alergias']); ?></div><?php endif; ?>
          <?php if (!empty($h['medicamentos_actuales'])): ?><div><strong>Medicamentos actuales:</strong> <?php echo htmlspecialchars($h['medicamentos_actuales']); ?></div><?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Script para el menú móvil (igual a client_dashboard)
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const sidebar = document.getElementById('sidebar');

  if (window.innerWidth <= 992) {
      mobileMenuToggle.style.display = 'block';
      mobileMenuToggle.addEventListener('click', () => {
          sidebar.classList.toggle('active');
      });
      document.querySelectorAll('.sidebar a').forEach(link => {
          link.addEventListener('click', () => {
              if (window.innerWidth <= 992) {
                  sidebar.classList.remove('active');
              }
          });
      });
  }
  window.addEventListener('resize', () => {
      if (window.innerWidth > 992) {
          mobileMenuToggle.style.display = 'none';
          sidebar.classList.remove('active');
      } else {
          mobileMenuToggle.style.display = 'block';
      }
  });

  document.getElementById('logout-button').addEventListener('click', function(e){
    e.preventDefault();
    Swal.fire({ 
      title:'¿Estás seguro?', 
      text:'¿Deseás cerrar sesión?', 
      icon:'warning',
      iconColor:'#f5a25d',
      showCancelButton:true, 
      confirmButtonText:'Sí, cerrar sesión',
      cancelButtonText:'Cancelar',
      confirmButtonColor:'#6c63ff',
      cancelButtonColor:'#6c757d'
    })
      .then((r)=>{ if(r.isConfirmed){ window.location.href='index.php'; } });
  });
</script>
</body>
</html>
