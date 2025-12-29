// ==========================================
// MÓDULO: ANALÍTICA (MATRIZ Y REPORTES)
// ==========================================

// --- MATRIZ ---
async function cargarMatriz() {
    const pid = document.getElementById('matrizSelectPerfil').value;
    const view = document.getElementById('matrizView');
    if(!pid) return;
    
    view.innerHTML = 'Cargando...';
    try {
        const res = await fetch(`${API}/matrix/${pid}`);
        const data = await res.json();
        
        let html = `<table class="table table-bordered table-sm text-center"><thead><tr><th>Comp \\ Asig</th>`;
        data.activities.forEach(a => html+=`<th class="vertical-text"><small>${a.name}</small></th>`);
        html+='</tr></thead><tbody>';
        
        data.competencies.forEach(c => {
            html+=`<tr><td class="text-start bg-light"><small>${c.description.substr(0,50)}...</small></td>`;
            data.activities.forEach(a => {
                const val = data.relations[`${a.id}_${c.id}`];
                let color = val==='high'?'bg-success':(val==='medium'?'bg-primary':(val==='low'?'bg-info':''));
                html+=`<td class="${color} text-white cursor-pointer" onclick="toggleCell(${a.id},${c.id},'${val}')">${val?'●':''}</td>`;
            });
            html+='</tr>';
        });
        view.innerHTML = html+'</tbody></table>';
    } catch(e) { view.innerHTML = 'Error matriz'; }
}

async function toggleCell(aid, cid, val) {
    const newVal = (val==='undefined'||!val)?'high':'none';
    await fetch(`${API}/matrix/toggle`, {method:'POST', body:JSON.stringify({activity_id:aid, competency_id:cid, level:newVal})});
    cargarMatriz();
}

// --- REPORTES (3.0) ---
async function cargarReporteBrechas() {
    const pid = document.getElementById('reportProfileSelect').value;
    const container = document.getElementById('reportChartContainer');
    if(!pid) return;
    container.innerHTML = 'Cargando análisis...';
    
    try {
        const res = await fetch(`${API}/reports/gap/${pid}`);
        const data = await res.json();
        window.lastReportData = data;
        
        if(data.length === 0) { container.innerHTML = 'Sin datos suficientes.'; return; }

        let html = '<table class="table table-sm"><thead><tr><th>Competencia</th><th>Docente</th><th>Estudiante</th><th>Brecha</th><th>Clip</th></tr></thead><tbody>';
        data.forEach(d => {
            const clip = d.evidence_url ? `<a href="${d.evidence_url}" target="_blank">📎</a>` : '-';
            html += `<tr><td><small>${d.code}</small></td><td>${d.real_score}</td><td>${d.perc_score}</td><td class="${d.gap<0?'text-danger':'text-success'}">${d.gap}</td><td>${clip}</td></tr>`;
        });
        container.innerHTML = html + '</tbody></table>';
    } catch(e) { container.innerHTML = 'Error reporte'; }
}

async function analizarReporteConIA() {
    if(!window.lastReportData) return;
    const div = document.getElementById('aiReportResult');
    div.innerHTML = 'Analizando...';
    const res = await fetch(`${API}/ai/analyze`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ data: JSON.stringify(window.lastReportData) }) });
    const json = await res.json();
    div.innerHTML = json.analysis.replace(/\n/g, "<br>");
}