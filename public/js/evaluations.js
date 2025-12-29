// ==========================================
// MÓDULO: EVALUACIONES (INSTRUMENTOS Y RESPUESTAS)
// ==========================================

// --- LISTAR ENCUESTAS (2.1) ---
async function listarEncuestas() {
    const container = document.getElementById('listaEncuestas');
    if(!container) return;
    container.innerHTML = 'Cargando...';
    try {
        const res = await fetch(`${API}/instruments`);
        const data = await res.json();
        container.innerHTML = '';
        if(data.length === 0) { container.innerHTML = 'No hay encuestas.'; return; }
        
        data.forEach(inst => {
            const btnResp = inst.status === 'published' ? `<button class="btn btn-sm btn-success ms-2" onclick="abrirResponderEncuesta(${inst.id})">▶ Responder</button>` : '';
            container.innerHTML += `<div class="list-group-item py-3"><div class="d-flex justify-content-between"><div><h6 class="fw-bold mb-0 cursor-pointer" onclick="verDetalleEncuesta(${inst.id})">${inst.title}</h6><small>${inst.profile_name}</small></div><div><span class="badge bg-${inst.status==='published'?'success':'secondary'}">${inst.status}</span>${btnResp}</div></div></div>`;
        });
    } catch(e) { container.innerHTML = 'Error'; }
}

// --- CREACIÓN DE INSTRUMENTOS ---
async function guardarNuevaEncuesta() {
    const pid = document.getElementById('newSurveyProfile').value;
    const title = document.getElementById('newSurveyTitleInput').value;
    if(!pid || !title) return alert("Completa los campos");
    
    const res = await fetch(`${API}/instruments`, { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ profile_id: pid, title: title, description: document.getElementById('newSurveyDesc').value }) });
    const json = await res.json();
    if(res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalNewSurvey')).hide();
        listarEncuestas();
        abrirPanelConstruccion(json.id, pid, title);
    }
}

async function abrirPanelConstruccion(instId, perfilId, titulo) {
    document.getElementById('panelConstruccion').classList.remove('d-none');
    document.getElementById('vistaPreviaInst').classList.add('d-none');
    document.getElementById('newSurveyTitle').innerText = titulo;
    document.getElementById('currentInstId').value = instId;

    const res = await fetch(`${API}/profiles/${perfilId}/competencies`);
    const comps = await res.json();
    const lista = document.getElementById('listaCompetenciasCheck');
    lista.innerHTML = '';
    comps.forEach(c => {
        lista.innerHTML += `<div class="form-check border-bottom py-2"><input class="form-check-input comp-check" type="checkbox" value="${c.id}"><label class="form-check-label small">${c.description}</label></div>`;
    });
}

async function agregarCompetenciasSeleccionadas() {
    const instId = document.getElementById('currentInstId').value;
    const ids = Array.from(document.querySelectorAll('.comp-check:checked')).map(cb => cb.value);
    if(ids.length === 0) return;
    await fetch(`${API}/instruments/add-competencies`, { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ instrument_id: instId, competency_ids: ids }) });
    document.getElementById('panelConstruccion').classList.add('d-none');
    document.getElementById('vistaPreviaInst').classList.remove('d-none');
    verDetalleEncuesta(instId);
}

async function verDetalleEncuesta(id) {
    const res = await fetch(`${API}/instruments/${id}`);
    const json = await res.json();
    let html = `<h5>${json.title}</h5><ul>`;
    json.criteria.forEach(c => html += `<li>${c.description}</li>`);
    html += `</ul>`;
    if(json.status !== 'published') html += `<button onclick="publicarEncuesta(${json.id})" class="btn btn-success w-100">Publicar</button>`;
    document.getElementById('vistaPreviaInst').innerHTML = html;
}

async function publicarEncuesta(id) {
    await fetch(`${API}/instruments/publish`, { method:'POST', body:JSON.stringify({id:id}) });
    verDetalleEncuesta(id);
}

// --- RÚBRICAS IA ---
async function generarRubrica() {
    const pid = document.getElementById('rubSelectPerfil').value;
    const txt = document.getElementById('rubPrompt').value;
    const resDiv = document.getElementById('rubResult');
    resDiv.innerHTML = 'Pensando...';
    try {
        const res = await fetch(`${API}/ai/generate`, { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ profile_id: pid, competency_text: txt }) });
        const json = await res.json();
        let html = `<h5>${json.title}</h5><table>`;
        json.criteria.forEach(c => html += `<tr><td>${c.description}</td><td>${c.weight}</td></tr>`);
        html += `</table><button class="btn btn-success mt-2" onclick='guardarRubricaIA(${pid}, ${JSON.stringify(json).replace(/'/g, "&#39;")})'>Guardar</button>`;
        resDiv.innerHTML = html;
    } catch(e) { resDiv.innerHTML = 'Error IA'; }
}

async function guardarRubricaIA(pid, data) {
    const payload = { profile_id: pid, title: data.title, description: data.description, criteria: data.criteria };
    await fetch(`${API}/instruments/save-ai`, { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    alert("Rúbrica Guardada");
}

// --- SALA DE EVALUACIÓN (2.3) ---
async function cargarDatosEvaluacion() {
    const rInst = await fetch(`${API}/instruments`);
    const insts = await rInst.json();
    const selI = document.getElementById('evalRubricaSelect');
    selI.innerHTML = '<option value="">-- Rúbrica --</option>';
    insts.forEach(i => selI.innerHTML += `<option value="${i.id}">${i.title}</option>`);

    const rStud = await fetch(`${API}/students`);
    const studs = await rStud.json();
    const selS = document.getElementById('evalStudentSelect');
    selS.innerHTML = '<option value="">-- Estudiante --</option>';
    studs.forEach(s => selS.innerHTML += `<option value="${s.id}">${s.name}</option>`);
}

async function cargarVistaPreviaRubrica() {
    const id = document.getElementById('evalRubricaSelect').value;
    if(!id) return;
    const res = await fetch(`${API}/instruments/${id}`);
    const json = await res.json();
    const container = document.getElementById('evalSheetContainer');
    let html = `<h6>${json.title}</h6>`;
    json.criteria.forEach(c => {
        html += `<div class="card mb-2 p-2"><p class="mb-1 fw-bold">${c.description}</p>
        <div class="btn-group w-100"><input type="radio" class="btn-check eval-radio" name="c_${c.id}" id="c${c.id}_1" value="1"><label class="btn btn-outline-danger" for="c${c.id}_1">1</label><input type="radio" class="btn-check eval-radio" name="c_${c.id}" id="c${c.id}_4" value="4"><label class="btn btn-outline-warning" for="c${c.id}_4">4</label><input type="radio" class="btn-check eval-radio" name="c_${c.id}" id="c${c.id}_7" value="7"><label class="btn btn-outline-success" for="c${c.id}_7">7</label></div></div>`;
    });
    container.innerHTML = html;
    document.getElementById('evalFooter').classList.remove('d-none');
}

async function guardarEvaluacion() {
    const iid = document.getElementById('evalRubricaSelect').value;
    const sid = document.getElementById('evalStudentSelect').value;
    const fileIn = document.getElementById('evidenceFile');
    const answers = {};
    document.querySelectorAll('.eval-radio:checked').forEach(r => answers[r.name.split('_')[1]] = r.value);
    
    if(Object.keys(answers).length === 0) return alert("Faltan respuestas");

    const res = await fetch(`${API}/evaluations`, { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ instrument_id: iid, student_id: sid, answers: answers }) });
    const json = await res.json();
    
    if(res.ok) {
        if(fileIn && fileIn.files.length > 0 && json.id) {
            const fd = new FormData();
            fd.append('evidence', fileIn.files[0]);
            fd.append('evaluation_id', json.id);
            await fetch(`${API}/upload/evidence`, { method:'POST', body: fd });
        }
        alert("Evaluación Guardada");
        document.getElementById('evalSheetContainer').innerHTML = 'Listo';
    }
}

// --- RESPONDER ENCUESTA (MODAL) ---
let currentSurveyId = null;
async function abrirResponderEncuesta(id) {
    currentSurveyId = id;
    new bootstrap.Modal(document.getElementById('modalAnswerSurvey')).show();
    // Cargar alumnos en select
    const r = await fetch(`${API}/students`); const s = await r.json();
    const sel = document.getElementById('surveyRespondentSelect');
    sel.innerHTML = ''; s.forEach(st => sel.innerHTML += `<option value="${st.id}">${st.name}</option>`);
    
    // Cargar preguntas
    const r2 = await fetch(`${API}/instruments/${id}`); const i = await r2.json();
    const c = document.getElementById('surveyQuestionsContainer');
    c.innerHTML = '';
    i.criteria.forEach(crit => {
        c.innerHTML += `<p>${crit.description}</p><div class="btn-group"><input type="radio" class="btn-check surv-radio" name="q_${crit.id}" id="q${crit.id}_1" value="1"><label class="btn btn-outline-danger" for="q${crit.id}_1">1</label><input type="radio" class="btn-check surv-radio" name="q_${crit.id}" id="q${crit.id}_4" value="4"><label class="btn btn-outline-success" for="q${crit.id}_4">4</label></div>`;
    });
}

async function enviarRespuestaEncuesta() {
    const sid = document.getElementById('surveyRespondentSelect').value;
    const answers = {};
    document.querySelectorAll('.surv-radio:checked').forEach(r => answers[r.name.split('_')[1]] = r.value);
    await fetch(`${API}/evaluations`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ instrument_id: currentSurveyId, student_id: sid, answers: answers }) });
    alert("Enviado");
    bootstrap.Modal.getInstance(document.getElementById('modalAnswerSurvey')).hide();
}