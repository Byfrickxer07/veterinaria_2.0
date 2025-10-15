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
    <title>Historial Clínico | Veterinaria</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #025162;
            --primary-hover: #013a47;
            --secondary: #027a8d;
            --secondary-hover: #015566;
            --accent: #ff6b35;
            --accent-hover: #e55a2b;
            --text: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 0.5rem;
            --transition: all 0.2s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-light);
            color: var(--text);
            line-height: 1.6;
        }

        /* Sidebar (sin cambios) */
        .sidebar { width: 275px; background-color: #025162; color: #ecf0f1; padding-top: 40px; display: flex; flex-direction: column; align-items: center; height: 100vh; transition: width 0.3s, background-color 0.3s, box-shadow 0.3s; position: fixed; top: 0; left: 0; z-index: 1000; box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .sidebar .profile-section{ text-align: center; margin-bottom: 20px; transition: margin-bottom 0.3s; }
        .sidebar .profile-image{ width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; transition: width 0.3s, height 0.3s; }
        .sidebar a{ display: flex; align-items: center; justify-content: flex-start; color: #ecf0f1; text-decoration: none; padding: 15px 20px; width: calc(100% - 40px); margin-bottom: 15px; background-color: #027a8d; border-radius: 12px; font-size: 16px; transition: background-color 0.3s, padding 0.3s, transform 0.15s ease-in-out; box-sizing: border-box; position: relative; }
        .sidebar a i{ margin-right: 10px; font-size: 18px; }
        .sidebar a:hover{ background-color: #03485f; transform: translateY(-1px); }
        .sidebar a.active{ background-color: #ff6b35; box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3); border: 2px solid rgba(255, 255, 255, 0.2); }
        .sidebar a.active:hover{ background-color: #e55a2b; }
        .sidebar span{ transition: opacity 0.3s ease; }
        .bottom-menu{ margin-top: auto; width: 100%; padding-bottom: 60px; display: flex; flex-direction: column; align-items: center; }
        .logout-button{ margin-top: 10px; display: flex; align-items: center; justify-content: center; }

        /* Contenido principal */
        .content {
            margin-left: 275px;
            padding: 2rem;
            min-height: 100vh;
        }

        /* Encabezado */
        .page-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Grid de mascotas */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 320px));
            gap: 1.5rem;
            margin: 0 auto 2rem;
            max-width: 1400px;
            justify-content: center;
            padding: 0 1rem;
        }

        .pet-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            border-left: 4px solid var(--primary);
        }

        .pet-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .pet-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .pet-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e6f7f9 0%, #b8e6ed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
            overflow: hidden;
            border: 2px solid rgba(2, 81, 98, 0.2);
        }

        .pet-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pet-info h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 0.15rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .pet-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-light);
        }

        .pet-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            width: 100%;
            margin-top: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            background: var(--secondary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            transition: transform 0.2s ease;
        }

        .btn:hover i {
            transform: translateX(3px);
        }

        .btn i {
            font-size: 1.1em;
        }

        /* Sección de historial */
        .history-section {
            margin-top: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .history-grid {
            display: grid;
            gap: 1.5rem;
        }

        .history-item {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .history-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .history-date {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .history-content {
            display: grid;
            gap: 0.75rem;
        }

        .history-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 1rem;
            align-items: flex-start;
        }

        .history-label {
            font-weight: 500;
            color: var(--text);
            font-size: 0.9375rem;
        }

        .history-value {
            color: var(--text-light);
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        /* Estados vacíos */
        .empty-state {
            background: var(--white);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            padding: 3rem 2rem;
            text-align: center;
            color: var(--text-light);
            margin: 2rem 0;
        }

        .empty-icon {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-text {
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            color: var(--text);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                padding-top: 5rem;
            }
            
            .sidebar { 
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .mobile-menu-toggle {
                display: block !important;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1100;
                background: var(--primary);
                border: none;
                color: white;
                width: 44px;
                height: 44px;
                border-radius: 50%;
                font-size: 1.5rem;
                cursor: pointer;
                box-shadow: var(--shadow-md);
                display: flex;
                align-items: center;
                justify-content: center;
                transition: var(--transition);
            }
            
            .mobile-menu-toggle:hover {
                background: var(--primary-hover);
                transform: translateY(-1px);
            }
            
            .history-row {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
        }

        @media (max-width: 576px) {
            .pets-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.75rem;
            }
            
            .section-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Botón de menú móvil -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none;">
        <i class='bx bx-menu'></i>
    </button>
    
    <!-- Barra lateral (sin cambios) -->
    <div class="sidebar" id="sidebar">
        <div class="profile-section">
            <img src="logo_perro.jpg" alt="Foto de Usuario" class="profile-image">
        </div>
        <a href="client_dashboard.php"><i class='bx bx-home'></i><span>Inicio</span></a>
        <a href="sacar_turno.php"><i class='bx bx-calendar-plus'></i><span>Sacar Turno</span></a>
        <a href="ver_turnos.php"><i class='bx bx-list-ul'></i><span>Mis Turnos</span></a>
        <a href="gestion_perfil.php"><i class='bx bx-user'></i><span>Mi Perfil</span></a>
        <a href="registrar_mascota.php"><i class='bx bx-plus-circle'></i><span>Mis Mascotas</span></a>
        <a href="historial_cliente.php" class="active"><i class='bx bx-notepad'></i><span>Historial Clínico</span></a>
        <a href="adopcion_page.php?view=client"><i class='bx bx-heart'></i><span>Adopción</span></a>
        <div class="bottom-menu">
            <a href="index.php" id="logout-button" class="logout-button">
                <i class='bx bx-log-out'></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="content">
        <div class="page-header">
            <h1 class="page-title">Historial Clínico</h1>
            <p class="page-subtitle">Consulta el historial médico de tus mascotas</p>
        </div>

        <h2 class="section-title">Mis Mascotas</h2>
        <div class="pets-grid">
            <?php if (count($mascotas) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class='bx bx-paw'></i>
                    </div>
                    <h3 class="empty-text">No tenés mascotas registradas</h3>
                    <p>Agregá una mascota para comenzar a ver su historial clínico.</p>
                    <a href="registrar_mascota.php" class="btn">
                        <i class='bx bx-plus'></i> Agregar Mascota
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($mascotas as $m): ?>
                    <div class="pet-card">
                        <div class="pet-header">
                            <div class="pet-avatar">
                                <?php if (!empty($m['foto'])): ?>
                                    <img src="<?php echo htmlspecialchars($m['foto']); ?>" alt="<?php echo htmlspecialchars($m['nombre']); ?>">
                                <?php else: ?>
                                    <i class='bx bx-paw'></i>
                                <?php endif; ?>
                            </div>
                            <div class="pet-info">
                                <h3><?php echo htmlspecialchars($m['nombre']); ?></h3>
                                <div class="pet-meta">
                                    <span><i class='bx bx-category'></i> <?php echo htmlspecialchars($m['especie']); ?></span>
                                    <span><i class='bx bx-dna'></i> <?php echo htmlspecialchars($m['raza']); ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="historial_cliente.php?mascota_id=<?php echo (int)$m['id']; ?>" class="btn">
                            <i class='bx bx-show'></i> Ver Historial
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($mascotaSeleccionada): ?>
            <div class="history-section">
                <h2 class="section-title">
                    <i class='bx bx-history'></i> 
                    Historial de <?php echo htmlspecialchars($mascotaSeleccionada['nombre']); ?>
                </h2>
                
                <?php if (count($historial) === 0): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class='bx bx-notepad'></i>
                        </div>
                        <h3 class="empty-text">No hay registros médicos</h3>
                        <p>No se encontraron registros médicos para esta mascota.</p>
                    </div>
                <?php else: ?>
                    <div class="history-grid">
                        <?php foreach ($historial as $h): ?>
                            <div class="history-item">
                                <div class="history-header">
                                    <div class="history-date">
                                        <i class='bx bx-calendar'></i>
                                        <?php echo date('d/m/Y', strtotime($h['fecha_consulta'])); ?>
                                    </div>
                                </div>
                                <div class="history-content">
                                    <?php if (!empty($h['motivo_consulta'])): ?>
                                        <div class="history-row">
                                            <div class="history-label">Motivo de consulta:</div>
                                            <div class="history-value"><?php echo nl2br(htmlspecialchars($h['motivo_consulta'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($h['diagnostico'])): ?>
                                        <div class="history-row">
                                            <div class="history-label">Diagnóstico:</div>
                                            <div class="history-value"><?php echo nl2br(htmlspecialchars($h['diagnostico'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($h['procedimientos_realizados'])): ?>
                                        <div class="history-row">
                                            <div class="history-label">Procedimientos:</div>
                                            <div class="history-value"><?php echo nl2br(htmlspecialchars($h['procedimientos_realizados'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($h['historial_vacunacion'])): ?>
                                        <div class="history-row">
                                            <div class="history-label">Vacunación:</div>
                                            <div class="history-value"><?php echo nl2br(htmlspecialchars($h['historial_vacunacion'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($h['alergias'])): ?>
                                        <div class="history-row">
                                            <div class="history-label">Alergias:</div>
                                            <div class="history-value"><?php echo nl2br(htmlspecialchars($h['alergias'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($h['medicamentos_actuales'])): ?>
                                        <div class="history-row">
                                            <div class="history-label">Medicamentos:</div>
                                            <div class="history-value"><?php echo nl2br(htmlspecialchars($h['medicamentos_actuales'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Script para el menú móvil
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');

        if (window.innerWidth <= 992) {
            mobileMenuToggle.style.display = 'flex';
            
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
            
            // Cerrar menú al hacer clic en un enlace
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('active');
                    }
                });
            });
        }

        // Actualizar en caso de cambio de tamaño de ventana
        window.addEventListener('resize', () => {
            if (window.innerWidth > 992) {
                mobileMenuToggle.style.display = 'none';
                sidebar.classList.remove('active');
            } else {
                mobileMenuToggle.style.display = 'flex';
            }
        });

        // Animación suave al hacer scroll
        document.addEventListener('DOMContentLoaded', () => {
            const historyItems = document.querySelectorAll('.history-item');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            historyItems.forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                observer.observe(item);
            });
        });
    </script>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="alertas_clientes.js"></script>
<script>
  // Script para el menú móvil (igual a client_dashboard)
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const sidebar = document.getElementById('sidebar');

  if (window.innerWidth <= 992) {
      mobileMenuToggle.style.display = 'block';
      
      mobileMenuToggle.addEventListener('click', () => {
          sidebar.classList.toggle('active');
      });
      
      // Cerrar menú al hacer clic en un enlace
      document.querySelectorAll('.sidebar a').forEach(link => {
          link.addEventListener('click', () => {
              if (window.innerWidth <= 992) {
                  sidebar.classList.remove('active');
              }
          });
      });
  }

  // Actualizar en caso de cambio de tamaño de ventana
  window.addEventListener('resize', () => {
      if (window.innerWidth > 992) {
          mobileMenuToggle.style.display = 'none';
          sidebar.classList.remove('active');
      } else {
          mobileMenuToggle.style.display = 'block';
      }
  });

  // Manejador de logout centralizado en alertas_clientes.js
</script>
</body>
</html>
