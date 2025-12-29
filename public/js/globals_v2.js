// public/js/globals.js
console.log("--> GLOBALS.JS CARGADO CORRECTAMENTE (v5)");

const API = '/api';

// --- AUTENTICACIÓN ---
async function checkAuth() {
    try {
        // Asegúrate de incluir 'credentials: include' para enviar la cookie
        const res = await fetch(`${API}/auth/me`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include' // <--- IMPORTANTE: Fuerza el envío de la cookie
        });

        if (!res.ok) throw new Error();
        const user = await res.json();
        
        const uName = document.getElementById('userName');
        const uRole = document.getElementById('userRole');
        if(uName) uName.innerText = user.name;
        if(uRole) uRole.innerText = user.role.toUpperCase();
        
        return user; // Retornamos el usuario si todo sale bien

    } catch (e) { 
        console.warn("Sesión no válida o expirada. Redirigiendo...");
        // DESCOMENTAR ESTA LÍNEA ES OBLIGATORIO:
        window.location.href = 'login.html'; 
        return null;
    }
}

async function logout() {
    await fetch(`${API}/auth/logout`, { method: 'POST' });
    window.location.href = 'login.html';
}

// --- HELPERS GLOBALES ---
async function cargarPerfilesGlobales() {
    try {
        const res = await fetch(`${API}/profiles`);
        const perfiles = await res.json();
        
        const ids = [
            'mallaSelectPerfil', 'rubSelectPerfil', 'matrizSelectPerfil', 
            'newSurveyProfile', 'reportProfileSelect', 'actividadSelectPerfil'
        ];

        ids.forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                // BLINDAJE TOTAL:
                // 1. Si el select tiene opciones, guardamos la primera. Si no, creamos una default.
                let firstOption = '<option value="">-- Seleccionar --</option>';
                if (el.options.length > 0) {
                    firstOption = el.options[0].outerHTML;
                }
                
                // 2. Reiniciamos el select con la opción base + los perfiles
                el.innerHTML = firstOption;
                perfiles.forEach(p => {
                    el.innerHTML += `<option value="${p.id}">${p.name}</option>`;
                });
            }
        });

        const counter = document.getElementById('dashProfilesCount');
        if(counter) counter.innerText = perfiles.length;
    } catch(e) { console.error("Error cargando globales:", e); }
}