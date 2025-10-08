<?php
session_start();
// Aceptar cualquiera de las dos convenciones usadas en el proyecto
$isAdmin = (
  (isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin') ||
  (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin')
);
// Determinar URL de regreso según rol
$userRole = isset($_SESSION['user_role']) ? strtolower($_SESSION['user_role']) : (isset($_SESSION['rol']) ? strtolower($_SESSION['rol']) : '');
$homeUrl = 'index.php';
if ($userRole === 'cliente') { $homeUrl = 'client_dashboard.php'; }
elseif ($userRole === 'admin') { $homeUrl = 'admin_dashboard.php'; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Adopción de Mascotas</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--primary:#f97316;--dark:#111827;--muted:#6b7280}
    *{box-sizing:border-box}
    body{margin:0;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial,sans-serif;background:linear-gradient(135deg,#fff7ed,#fff1f2);color:#0f172a}
    .container{max-width:1120px;margin:0 auto;padding:16px}
    .header{background:#fff;border-bottom:1px solid #e5e7eb}
    .header-inner{display:flex;align-items:center;justify-content:space-between;padding:16px}
    .brand{font-weight:800;font-size:22px;color:#111827}
    .btn{border:0;border-radius:10px;padding:10px 14px;cursor:pointer;font-weight:700}
    .btn.primary{background:var(--primary);color:#fff}
    .btn.dark{background:var(--dark);color:#fff}
    .btn.muted{background:#e5e7eb;color:#111827}
    /* Sidebar */
    .sidebar{position:fixed;top:0;left:0;bottom:0;width:240px;background:#025162;color:#fff;padding:18px 14px;display:flex;flex-direction:column;gap:10px}
    .sidebar .side-brand{font-weight:800;font-size:20px;margin-bottom:10px}
    .side-nav{display:flex;flex-direction:column;gap:8px;margin-top:8px}
    .side-link{display:flex;align-items:center;gap:10px;background:#027a8d;color:#fff;border:none;border-radius:10px;padding:10px 12px;cursor:pointer;font-weight:700;text-align:left}
    .side-link:hover{background:#046476}
    .main{margin-left:240px}
    .toolbar{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:12px;display:flex;gap:10px;align-items:center}
    .toolbar input,.toolbar select{flex:1;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-top:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 6px 20px rgba(249,115,22,.12)}
    .card img{width:100%;height:180px;object-fit:cover}
    .card-body{padding:14px}
    .badge{display:inline-block;background:#dcfce7;color:#166534;font-size:12px;font-weight:800;padding:4px 8px;border-radius:9999px}
    .muted{color:var(--muted);font-size:14px}
    .title{font-size:20px;font-weight:800;margin:0 0 4px}
    .actions{display:flex;gap:8px;margin-top:10px}
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);padding:18px;z-index:50}
    .modal-content{max-width:760px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;max-height:92vh}
    .modal-header{padding:12px 16px;background:#111827;color:#fff;display:flex;justify-content:space-between;align-items:center}
    .modal-body{padding:16px;overflow:auto}
    .modal-footer{padding:12px 16px;background:#f3f4f6;display:flex;justify-content:flex-end;gap:8px}
    .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .form-group{display:flex;flex-direction:column;gap:6px}
    .form-group label{font-size:13px;font-weight:700}
    .form-group input,.form-group select,.form-group textarea{padding:10px 12px;border:1px solid #d1d5db;border-radius:10px}
    .details-img{width:100%;height:240px;object-fit:cover}
  </style>
</head>
<body>
  
  <div>
  <div class="header">
    <div class="header-inner container">
      <a class="brand" href="<?php echo htmlspecialchars($homeUrl); ?>" title="Volver">🐾 AdoptaMe</a>
      <div style="display:flex;gap:10px">
        <?php if ($isAdmin): ?>
          <button id="btnAdmin" class="btn dark">Administrar</button>
          <button id="btnClient" class="btn muted" style="display:none">Ver Sitio</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container" id="clientView">
    <div class="toolbar" style="margin:16px 0">
      <input id="searchInput" type="text" placeholder="Buscar por nombre o raza..." />
      <select id="typeFilter">
        <option value="todos">Todos</option>
        <option value="Perro">Perros</option>
        <option value="Gato">Gatos</option>
      </select>
    </div>
    <div id="petsGrid" class="grid"></div>
  </div>

  <?php if ($isAdmin): ?>
  <div class="container" id="adminView" style="display:none">
    <div style="display:flex;justify-content:space-between;align-items:center;margin:16px 0">
      <h2 style="margin:0">Panel de Administración</h2>
      <button class="btn primary" id="btnNew">Nueva Mascota</button>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="toolbar" style="margin-bottom:12px">
          <input id="adminSearch" type="text" placeholder="Buscar..." />
        </div>
        <div id="adminList" class="grid"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div id="detailsModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header"><strong id="detailsTitle">Detalles</strong><button class="btn muted" onclick="closeDetails()">Cerrar</button></div>
      <div class="modal-body">
        <img id="detailsImage" class="details-img" alt="Pet" />
        <div id="detailsInfo" style="margin-top:12px"></div>
        <button id="btnContact" class="btn primary" style="margin-top:12px;width:100%">Contactar para Adoptar</button>
      </div>
    </div>
  </div>

  <?php if ($isAdmin): ?>
  <div id="formModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header"><strong id="formTitle">Nueva Mascota</strong><button class="btn muted" onclick="closeForm()">Cerrar</button></div>
      <form id="petForm" class="modal-body" enctype="multipart/form-data">
        <input type="hidden" name="id" id="f_id" />
        <div class="form-grid">
          <div class="form-group"><label>Nombre</label><input name="name" id="f_name" required /></div>
          <div class="form-group"><label>Tipo</label><select name="type" id="f_type"><option>Perro</option><option>Gato</option></select></div>
          <div class="form-group"><label>Raza</label><input name="breed" id="f_breed" required /></div>
          <div class="form-group"><label>Edad</label><input name="age" id="f_age" /></div>
          <div class="form-group"><label>Género</label><select name="gender" id="f_gender"><option>Macho</option><option>Hembra</option></select></div>
          <div class="form-group"><label>Tamaño</label><select name="size" id="f_size"><option>Pequeño</option><option>Mediano</option><option>Grande</option></select></div>
        </div>
        <div class="form-group"><label>Descripción</label><textarea name="description" id="f_description" rows="3"></textarea></div>
        <div class="form-grid">
          <div class="form-group"><label>Nombre del Refugio</label><input name="location" id="f_location" /></div>
          <div class="form-group"><label>Dirección</label><input name="address" id="f_address" /></div>
        </div>
        <div class="form-grid">
          <div class="form-group"><label>Teléfono</label><input name="phone" id="f_phone" /></div>
          <div class="form-group"><label>Email</label><input name="email" id="f_email" type="email" /></div>
        </div>
        <div class="form-group"><label>Estado</label><select name="status" id="f_status"><option>Disponible</option><option>Reservado</option><option>Adoptado</option></select></div>
        <div class="form-group"><label>Imagen (se guardará en uploads/)</label><input type="file" name="image_file" id="f_image_file" accept="image/*" /></div>
      </form>
      <div class="modal-footer">
        <button class="btn muted" onclick="closeForm()">Cancelar</button>
        <button class="btn primary" id="btnSave">Guardar</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

<script>
const el = (id)=>document.getElementById(id);
let PETS = [];
let VIEW = 'client';

function switchTo(view){
  VIEW = view;
  const client = el('clientView');
  const admin = el('adminView');
  if (client) client.style.display = view==='client'?'block':'none';
  if (admin) admin.style.display = view==='admin'?'block':'none';
  const btnClient = el('btnClient');
  if (btnClient) btnClient.style.display = view==='admin'?'inline-flex':'none';
}

document.getElementById('btnAdmin')?.addEventListener('click', ()=>switchTo('admin'));
document.getElementById('btnClient')?.addEventListener('click', ()=>switchTo('client'));
document.getElementById('navClient')?.addEventListener('click', ()=>switchTo('client'));
document.getElementById('navAdmin')?.addEventListener('click', ()=>switchTo('admin'));

async function loadPets(){
  const res = await fetch('mascotas_list.php');
  const data = await res.json();
  PETS = data.pets || [];
  renderClient();
  renderAdmin();
}

function renderClient(){
  const q = (el('searchInput').value||'').toLowerCase().trim();
  const t = (el('typeFilter').value||'todos');
  const list = PETS.filter(p=>{
    const ms = (p.name||'').toLowerCase().includes(q) || (p.breed||'').toLowerCase().includes(q);
    const mt = (t==='todos') || (p.type===t);
    return ms && mt;
  });
  const grid = el('petsGrid');
  grid.innerHTML = list.map(p=>{
    const img = p.image && p.image.startsWith('http') ? p.image : (p.image ? p.image : '');
    return `<div class="card">
      ${img?`<img src="${img}" alt="${p.name}">`:''}
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:start;gap:8px">
          <div>
            <div class="title">${p.name||''}</div>
            <div class="muted">${p.breed||''} • ${p.age||''}</div>
          </div>
          <span class="badge">${p.status||''}</span>
        </div>
        <p class="muted" style="margin:8px 0 10px">${(p.description||'').slice(0,100)}...</p>
        <button class="btn primary" style="width:100%" onclick='openDetails(${JSON.stringify(p.id)})'>Ver Detalles</button>
      </div>
    </div>`;
  }).join('');
}

function openDetails(id){
  const p = PETS.find(x=>x.id===id);
  if(!p) return;
  el('detailsTitle').textContent = p.name || '';
  const img = p.image && p.image.startsWith('http') ? p.image : (p.image ? p.image : '');
  el('detailsImage').src = img || '';
  el('detailsInfo').innerHTML = `
    <div class="form-grid">
      <div><div class="muted">Tipo</div><div><strong>${p.type||''}</strong></div></div>
      <div><div class="muted">Raza</div><div><strong>${p.breed||''}</strong></div></div>
      <div><div class="muted">Edad</div><div><strong>${p.age||''}</strong></div></div>
      <div><div class="muted">Género</div><div><strong>${p.gender||''}</strong></div></div>
      <div><div class="muted">Tamaño</div><div><strong>${p.size||''}</strong></div></div>
      <div><div class="muted">Estado</div><div><strong>${p.status||''}</strong></div></div>
    </div>
    <div style="margin-top:12px"><strong>Descripción</strong><div class="muted">${p.description||''}</div></div>
    <div style="margin-top:12px"><strong>Contacto</strong>
      <div class="muted">${p.location||''}</div>
      <div class="muted">${p.address||''}</div>
      <div class="muted">${p.phone||''}</div>
      <div class="muted">${p.email||''}</div>
    </div>`;
  document.getElementById('btnContact').onclick = ()=>{ if(p.email){ window.location.href = `mailto:${p.email}?subject=Consulta sobre ${encodeURIComponent(p.name||'Mascota')}`; } };
  const m = el('detailsModal'); m.style.display='block'; m.setAttribute('aria-hidden','false');
}

function closeDetails(){ const m = el('detailsModal'); m.style.display='none'; m.setAttribute('aria-hidden','true'); }

function renderAdmin(){
  const adminList = el('adminList');
  if (!adminList) return;
  const q = (el('adminSearch').value||'').toLowerCase().trim();
  const list = PETS.filter(p => (p.name||'').toLowerCase().includes(q) || (p.breed||'').toLowerCase().includes(q));
  adminList.innerHTML = list.map(p=>{
    const img = p.image && p.image.startsWith('http') ? p.image : (p.image ? p.image : '');
    return `<div class="card">
      ${img?`<img src="${img}" alt="${p.name}">`:''}
      <div class="card-body">
        <div class="title">${p.name||''}</div>
        <div class="muted">${p.type||''} • ${p.breed||''}</div>
        <div class="actions">
          <button class="btn muted" onclick='editPet(${JSON.stringify(p.id)})'>Editar</button>
          <button class="btn" style="background:#ef4444;color:#fff" onclick='deletePet(${JSON.stringify(p.id)})'>Eliminar</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

function openForm(){ const fm = el('formModal'); if(fm){ fm.style.display='block'; fm.setAttribute('aria-hidden','false'); } }
function closeForm(){ const fm = el('formModal'); if(fm){ fm.style.display='none'; fm.setAttribute('aria-hidden','true'); } document.getElementById('petForm')?.reset(); document.getElementById('f_id').value=''; }

document.getElementById('btnNew')?.addEventListener('click', ()=>{ document.getElementById('formTitle').textContent='Nueva Mascota'; openForm(); });
document.getElementById('adminSearch')?.addEventListener('input', renderAdmin);
document.getElementById('searchInput').addEventListener('input', renderClient);
document.getElementById('typeFilter').addEventListener('change', renderClient);

function fillForm(p){
  document.getElementById('f_id').value = p.id || '';
  document.getElementById('f_name').value = p.name||'';
  document.getElementById('f_type').value = p.type||'Perro';
  document.getElementById('f_breed').value = p.breed||'';
  document.getElementById('f_age').value = p.age||'';
  document.getElementById('f_gender').value = p.gender||'Macho';
  document.getElementById('f_size').value = p.size||'Mediano';
  document.getElementById('f_description').value = p.description||'';
  document.getElementById('f_location').value = p.location||'';
  document.getElementById('f_address').value = p.address||'';
  document.getElementById('f_phone').value = p.phone||'';
  document.getElementById('f_email').value = p.email||'';
  document.getElementById('f_status').value = p.status||'Disponible';
}

function editPet(id){
  const p = PETS.find(x=>x.id===id);
  if(!p) return;
  fillForm(p);
  document.getElementById('formTitle').textContent = 'Editar Mascota';
  openForm();
}

async function deletePet(id){
  if(!confirm('¿Eliminar esta mascota?')) return;
  const fd = new FormData();
  fd.append('id', id);
  const res = await fetch('mascotas_delete.php', { method:'POST', body: fd });
  const txt = await res.text();
  if(txt.startsWith('ok')){ await loadPets(); }
  else alert(txt);
}

document.getElementById('btnSave')?.addEventListener('click', async ()=>{
  const form = document.getElementById('petForm');
  const fd = new FormData(form);
  const res = await fetch('mascotas_save.php', { method:'POST', body: fd });
  const txt = await res.text();
  if(txt.startsWith('ok')){
    closeForm();
    await loadPets();
  } else {
    alert(txt);
  }
});

window.addEventListener('click',(e)=>{ if(e.target===document.getElementById('detailsModal')) closeDetails(); if(e.target===document.getElementById('formModal')) closeForm();});

loadPets();
</script>
  </div>
</body>
</html>
