// public/js/actividades_v2.js

const API_ACT = '/api'; 

// ==========================================
// 1. GENERADOR DE VENTANA (IDs Únicos) 🛡️
// ==========================================
function obtenerModal() {
    const viejo = document.getElementById('modalActividadDinamica');
    if (viejo) viejo.remove();

    // Nota: Usamos IDs con prefijo 'dyn_' para que no choquen con app.html
    const html = `
    <div class="modal fade" id="modalActividadDinamica" tabindex="-1" style="z-index: 9999;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="dyn_modalTitle">Gestión de Actividad</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', html);
    return document.getElementById('modalActividadDinamica');
}

// ==========================================
// 2. FUNCIONES PRINCIPALES
// ==========================================

function crearNuevaActividad() {
    const select = document.getElementById('actividadSelectPerfil');
    const perfilId = select ? select.value : null;

    if (!perfilId) return alert("⚠️ Primero selecciona un Perfil de Egreso en la lista.");

    abrirModalActividad(null, perfilId);
}

async function abrirModalActividad(id, perfilId) {
    const modalEl = obtenerModal();
    const modalBody = modalEl.querySelector('.modal-body');
    const modalTitle = modalEl.querySelector('#dyn_modalTitle');

    let data = { 
        name: "", credits: "", semester: "", custom_id: "",
        hours_total: 0, hours_teaching: 0, hours_autonomous: 0,
        req_attendance: 0, prerequisites: 0
    };

    if (id) {
        modalTitle.innerText = "✏️ Editando Actividad";
        try {
            const res = await fetch(`${API_ACT}/activities/${id}/full`);
            if(res.ok) data = await res.json();
        } catch (e) { console.error(e); }
    } else {
        modalTitle.innerText = "➕ Nueva Actividad";
    }

    // FORMULARIO CON IDs ÚNICOS (dyn_*)
    modalBody.innerHTML = `
        <input type="hidden" id="dyn_actId" value="${id || ''}">
        <input type="hidden" id="dyn_actProfileId" value="${perfilId}">
        
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label fw-bold">Nombre Asignatura <span class="text-danger">*</span></label>
                <input type="text" id="dyn_actName" class="form-control" value="${data.name}" placeholder="Ej: Introducción a las Ciencias">
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted">Código Interno</label>
                <input type="text" id="dyn_actCustomId" class="form-control" value="${data.custom_id || ''}" placeholder="(Opcional)">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Semestre</label>
                <input type="number" id="dyn_actSemester" class="form-control" value="${data.semester}" placeholder="Ej: 1">
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold text-primary">Créditos SCT</label>
                <div class="input-group">
                    <input type="number" id="dyn_actCredits" class="form-control border-primary" value="${data.credits}" oninput="calcularHorasPorCredito()" placeholder="Ej: 5">
                    <span class="input-group-text bg-light text-muted">x 23 hrs</span>
                </div>
            </div>
        </div>

        <div class="card bg-light border-0 mb-3">
            <div class="card-body py-2">
                <h6 class="card-subtitle mb-2 text-muted small">Distribución Horaria Automática</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="small fw-bold">Total (Crono)</label>
                        <input type="number" id="dyn_actHrsTotal" class="form-control bg-secondary text-white fw-bold" value="${data.hours_total}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="small">Docencia</label>
                        <input type="number" id="dyn_actHrsDoc" class="form-control" value="${data.hours_teaching}" oninput="balancearHoras('doc')">
                    </div>
                    <div class="col-md-4">
                        <label class="small">Autónomas</label>
                        <input type="number" id="dyn_actHrsAut" class="form-control" value="${data.hours_autonomous}" oninput="balancearHoras('aut')">
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check form-switch pt-2">
                    <input class="form-check-input" type="checkbox" id="dyn_checkAsistencia" ${data.req_attendance == 1 ? 'checked' : ''}>
                    <label class="form-check-label" for="dyn_checkAsistencia">Asistencia Obligatoria</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch pt-2">
                    <input class="form-check-input" type="checkbox" id="dyn_checkPrerrequisitos" ${data.prerequisites == 1 ? 'checked' : ''}>
                    <label class="form-check-label" for="dyn_checkPrerrequisitos">Tiene Prerrequisitos</label>
                </div>
            </div>
        </div>
        
        <div class="text-end pt-3 border-top">
            <button class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-success px-4 fw-bold" onclick="guardarActividad()">Guardar Actividad</button>
        </div>
    `;

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

// ==========================================
// 3. LÓGICA DE HORAS (IDs actualizados)
// ==========================================
function calcularHorasPorCredito() {
    const creditos = parseFloat(document.getElementById('dyn_actCredits').value) || 0;
    const total = Math.round(creditos * 23); 
    document.getElementById('dyn_actHrsTotal').value = total;
    document.getElementById('dyn_actHrsDoc').value = 0;
    document.getElementById('dyn_actHrsAut').value = total;
}

function balancearHoras(origen) {
    const total = parseInt(document.getElementById('dyn_actHrsTotal').value) || 0;
    const inputDoc = document.getElementById('dyn_actHrsDoc');
    const inputAut = document.getElementById('dyn_actHrsAut');

    if (total === 0) return;

    if (origen === 'doc') {
        let valDoc = parseInt(inputDoc.value) || 0;
        if (valDoc > total) { valDoc = total; inputDoc.value = total; }
        inputAut.value = total - valDoc;
    } 
    else if (origen === 'aut') {
        let valAut = parseInt(inputAut.value) || 0;
        if (valAut > total) { valAut = total; inputAut.value = total; }
        inputDoc.value = total - valAut;
    }
}

// ==========================================
// 4. GUARDADO (IDs actualizados)
// ==========================================
async function guardarActividad() {
    // AHORA SÍ LEERÁ EL CAMPO CORRECTO
    const nombre = document.getElementById('dyn_actName').value;
    
    if(!nombre || !nombre.trim()) return alert("❌ El nombre es obligatorio");

    const data = {
        id: document.getElementById('dyn_actId').value,
        profile_id: document.getElementById('dyn_actProfileId').value,
        name: nombre,
        credits: document.getElementById('dyn_actCredits').value,
        semester: document.getElementById('dyn_actSemester').value,
        custom_id: document.getElementById('dyn_actCustomId').value,
        hours_total: document.getElementById('dyn_actHrsTotal').value,
        hours_teaching: document.getElementById('dyn_actHrsDoc').value,
        hours_autonomous: document.getElementById('dyn_actHrsAut').value,
        req_attendance: document.getElementById('dyn_checkAsistencia').checked ? 1 : 0,
        prerequisites: document.getElementById('dyn_checkPrerrequisitos').checked ? 1 : 0
    };

    try {
        const res = await fetch(`${API_ACT}/activities`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        if(res.ok) {
            const modalEl = document.getElementById('modalActividadDinamica');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            cargarActividadesPorPerfil(data.profile_id);
            alert("✅ Guardado correctamente");
        } else {
            const text = await res.text();
            try {
                const err = JSON.parse(text);
                alert("Error: " + (err.error || "Desconocido"));
            } catch(e) {
                alert("❌ Error en el servidor al guardar.");
            }
        }
    } catch(e) { 
        console.error(e); 
        alert("❌ Error de conexión"); 
    }
}

// ... Mantén la función cargarActividadesPorPerfil y eliminarActividad igual que antes ...
async function cargarActividadesPorPerfil(profileId) {
    if (!profileId) {
        const select = document.getElementById('actividadSelectPerfil');
        if (select) profileId = select.value;
    }
    if (!profileId) return;

    const contenedor = document.getElementById('listaActividadesContainer');
    if (!contenedor) return;

    contenedor.innerHTML = '<div class="text-center p-3 text-muted">Cargando...</div>';

    try {
        const res = await fetch(`${API_ACT}/activities?profile_id=${profileId}`);
        const data = await res.json();

        if (!data || data.length === 0) {
            contenedor.innerHTML = '<div class="alert alert-info shadow-sm">ℹ️ No hay actividades creadas para este perfil.</div>';
            return;
        }

        let html = `
            <div class="table-responsive bg-white shadow-sm rounded">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Sem</th>
                            <th>Asignatura</th>
                            <th class="text-center">Créditos</th>
                            <th class="text-center">Hrs Totales</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>`;

        data.forEach(act => {
            html += `
                <tr>
                    <td class="ps-3"><span class="badge bg-secondary rounded-pill">${act.semester}</span></td>
                    <td>
                        <div class="fw-bold text-primary">${act.name}</div>
                        <small class="text-muted" style="font-size:0.75rem">${act.custom_id || ''}</small>
                    </td>
                    <td class="text-center fw-bold">${act.credits}</td>
                    <td class="text-center text-muted">${act.hours_total}</td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="abrirModalActividad(${act.id}, ${act.profile_id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarActividad(${act.id}, ${act.profile_id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });

        html += '</tbody></table></div>';
        contenedor.innerHTML = html;

    } catch (e) {
        console.error(e);
        contenedor.innerHTML = '<div class="alert alert-danger">Error al cargar datos.</div>';
    }
}

async function eliminarActividad(id, profileId) {
    if(!confirm("¿Seguro que deseas eliminar esta actividad?")) return;
    try {
        await fetch(`${API_ACT}/activities/${id}`, { method: 'DELETE' });
        cargarActividadesPorPerfil(profileId);
    } catch(e) { alert("Error al eliminar"); }
}