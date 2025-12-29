// public/js/perfiles_v2.js
const API_PROF = '/api';

// ==========================================
// 1. GESTIÓN DE PERFILES
// ==========================================

async function cargarListaPerfiles() {
    const contenedor = document.getElementById('listaPerfilesSide');
    if (!contenedor) return;

    try {
        const res = await fetch(`${API_PROF}/profiles`);
        const perfiles = await res.json();
        
        contenedor.innerHTML = '';
        perfiles.forEach(p => {
            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action border-0 py-3';
            item.innerHTML = `
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 fw-bold text-dark">${p.name}</h6>
                </div>
                <small class="text-muted"><i class="bi bi-qr-code"></i> ${p.code || 'S/C'}</small>
            `;
            item.onclick = () => cargarPerfilDetalle(p.id);
            contenedor.appendChild(item);
        });
        
        const dashCount = document.getElementById('dashProfilesCount');
        if (dashCount) dashCount.innerText = perfiles.length;

    } catch (e) { console.error("Error cargando perfiles:", e); }
}

async function cargarPerfilDetalle(id) {
    try {
        const res = await fetch(`${API_PROF}/profiles/${id}`);
        const data = await res.json();

        document.getElementById('profId').value = data.id;
        document.getElementById('profName').value = data.name;
        document.getElementById('profCode').value = data.code;
        document.getElementById('profCareer').value = data.career;
        document.getElementById('profFaculty').value = data.faculty;
        document.getElementById('profDesc').value = data.description || '';
        document.getElementById('profDirector').value = data.director || '';
        document.getElementById('profDuration').value = data.duration || '';
        document.getElementById('profAccreditation').value = data.accreditation_years || '';
        
        const checkLic = document.getElementById('profLicenciatura');
        if(checkLic) {
            checkLic.checked = (data.has_licentiate == 1);
            toggleLicenciaturaUI();
        }

        document.getElementById('tituloFormPerfil').innerHTML = `<i class="bi bi-pencil-square"></i> Editando: ${data.code}`;
        document.getElementById('btnEliminarPerfil').classList.remove('d-none');
        document.getElementById('seccionCompetencias').classList.remove('d-none');
        
        cargarCompetenciasPerfil(id);

    } catch(e) { console.error(e); }
}

function limpiarFormularioPerfil() {
    document.getElementById('formPerfil').reset();
    document.getElementById('profId').value = '';
    document.getElementById('tituloFormPerfil').innerHTML = '<i class="bi bi-plus-circle"></i> Nuevo Perfil';
    document.getElementById('btnEliminarPerfil').classList.add('d-none');
    document.getElementById('seccionCompetencias').classList.add('d-none');
}

async function guardarPerfil(e) {
    if(e) e.preventDefault();
    const id = document.getElementById('profId').value;
    
    const data = {
        name: document.getElementById('profName').value,
        career: document.getElementById('profCareer').value,
        faculty: document.getElementById('profFaculty').value,
        description: document.getElementById('profDesc').value,
        director: document.getElementById('profDirector').value,
        duration: document.getElementById('profDuration').value,
        accreditation_years: document.getElementById('profAccreditation').value,
        has_licentiate: document.getElementById('profLicenciatura').checked ? 1 : 0
    };

    if(id) data.id = id;

    try {
        const res = await fetch(`${API_PROF}/profiles`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        if(res.ok) {
            alert("Perfil guardado correctamente");
            cargarListaPerfiles();
            if(!id) limpiarFormularioPerfil();
        } else {
            alert("Error al guardar perfil");
        }
    } catch(e) { alert("Error de conexión"); }
}

function toggleLicenciaturaUI() {}

// ==========================================
// 2. COMPETENCIAS TRANSVERSALES
// ==========================================

async function cargarCompetenciasTransversales() {
    console.log("Cargando Transversales...");
    const contenedorSello = document.getElementById('listaSello');
    const contenedorLic = document.getElementById('listaLicenciatura');

    if (contenedorSello) contenedorSello.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
    if (contenedorLic) contenedorLic.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';

    try {
        const resSello = await fetch(`${API_PROF}/competencies?scope=seal`);
        const dataSello = await resSello.json();
        renderListaTransversal(contenedorSello, dataSello, 'seal');

        const resLic = await fetch(`${API_PROF}/competencies?scope=licensure`);
        const dataLic = await resLic.json();
        renderListaTransversal(contenedorLic, dataLic, 'licensure');

    } catch (e) {
        console.error("Error cargando transversales:", e);
        if(contenedorSello) contenedorSello.innerHTML = '<div class="text-danger small">Error de carga</div>';
    }
}

function renderListaTransversal(contenedor, lista, type) {
    if (!contenedor) return;
    contenedor.innerHTML = '';

    if (!lista || lista.length === 0) {
        contenedor.innerHTML = '<div class="text-muted small p-2">No hay competencias registradas.</div>';
        return;
    }

    lista.forEach(c => {
        const div = document.createElement('div');
        div.className = 'list-group-item list-group-item-action';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                    <div class="fw-bold small">${c.description}</div>
                    <span class="badge bg-light text-dark border mt-1">Nivel 1: ${c.level_1 ? 'Definido' : 'Pendiente'}</span>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary btn-sm" onclick="editarTransversal(${c.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="eliminarCompetencia(${c.id}, '${type}')" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

function abrirModalTransversal(tipo) {
    document.getElementById('transId').value = '';
    document.getElementById('transScope').value = tipo; 
    document.getElementById('transDesc').value = '';
    document.getElementById('transL1').value = '';
    document.getElementById('transL2').value = '';
    document.getElementById('transL3').value = '';

    const titulo = (tipo === 'seal') ? 'Nueva Competencia Sello' : 'Nueva Comp. Licenciatura';
    document.getElementById('tituloModalTrans').innerText = titulo;

    new bootstrap.Modal(document.getElementById('modalTransversal')).show();
}

// ========== NUEVA FUNCIÓN: EDITAR TRANSVERSAL ==========
async function editarTransversal(id) {
    try {
        // Obtener datos de la competencia desde el servidor
        const res = await fetch(`${API_PROF}/competencies/${id}`);
        if (!res.ok) {
            alert('No se pudo cargar la competencia');
            return;
        }
        const c = await res.json();

        // Llenar el formulario del modal
        document.getElementById('transId').value = c.id;
        document.getElementById('transScope').value = c.scope || 'seal';
        document.getElementById('transDesc').value = c.description || '';
        document.getElementById('transL1').value = c.level_1 || '';
        document.getElementById('transL2').value = c.level_2 || '';
        document.getElementById('transL3').value = c.level_3 || '';

        // Cambiar título del modal
        const titulo = (c.scope === 'seal') ? 'Editar Competencia Sello' : 'Editar Comp. Licenciatura';
        document.getElementById('tituloModalTrans').innerText = titulo;

        // Abrir modal
        new bootstrap.Modal(document.getElementById('modalTransversal')).show();

    } catch (e) {
        console.error("Error al cargar competencia:", e);
        alert("Error de conexión");
    }
}

// ========== GUARDAR TRANSVERSAL (CREAR O EDITAR) ==========
async function guardarTransversal() {
    const id = document.getElementById('transId').value;
    const scope = document.getElementById('transScope').value;
    
    const data = {
        profile_id: null,
        scope: scope,
        description: document.getElementById('transDesc').value,
        level_1: document.getElementById('transL1').value,
        level_2: document.getElementById('transL2').value,
        level_3: document.getElementById('transL3').value
    };

    if(!data.description) return alert("La descripción es obligatoria");

    try {
        let url = `${API_PROF}/competencies`;
        let method = 'POST';

        // Si hay ID, es una edición - usar PUT
        if (id) {
            url = `${API_PROF}/competencies/${id}`;
            method = 'PUT';
        }

        const res = await fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        if(res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalTransversal')).hide();
            cargarCompetenciasTransversales(); 
            const mensaje = id ? "Competencia actualizada" : "Competencia guardada";
            alert(mensaje);
        } else {
            const err = await res.json();
            alert("Error: " + (err.error || "No se pudo guardar"));
        }
    } catch(e) { console.error(e); alert("Error de conexión"); }
}

async function eliminarCompetencia(id, typeSource) {
    if(!confirm("¿Eliminar esta competencia?")) return;
    try {
        await fetch(`${API_PROF}/competencies/${id}`, { method: 'DELETE' });
        cargarCompetenciasTransversales();
    } catch(e) { alert("Error al eliminar"); }
}

// ==========================================
// EXPORTAR FUNCIONES GLOBALES
// ==========================================
window.cargarListaPerfiles = cargarListaPerfiles;
window.cargarPerfilDetalle = cargarPerfilDetalle;
window.limpiarFormularioPerfil = limpiarFormularioPerfil;
window.guardarPerfil = guardarPerfil;
window.toggleLicenciaturaUI = toggleLicenciaturaUI;
window.cargarCompetenciasTransversales = cargarCompetenciasTransversales;
window.abrirModalTransversal = abrirModalTransversal;
window.editarTransversal = editarTransversal;  // <-- NUEVA
window.guardarTransversal = guardarTransversal;
window.eliminarCompetencia = eliminarCompetencia;

if (!window.cargarCompetenciasPerfil) {
    window.cargarCompetenciasPerfil = function(id) { console.log("Cargando competencias específicas del perfil " + id); };
}