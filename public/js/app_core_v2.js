// public/js/app_core_v2.js

// Al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    // 1. Verificar sesión (Simulado o Real)
    const user = localStorage.getItem('sapa_user_name') || 'Administrador';
    const role = localStorage.getItem('sapa_user_role') || 'ADMIN';
    
    const elName = document.getElementById('userName');
    const elRole = document.getElementById('userRole');
    if(elName) elName.innerText = user;
    if(elRole) elRole.innerText = role;

    // 2. Cargar Dashboard por defecto
    navTo('dashboard');
    
    // 3. Cargar lista de perfiles en segundo plano para tener los selects listos
    if(window.cargarListaPerfiles) window.cargarListaPerfiles();
});

// FUNCIÓN PRINCIPAL DE NAVEGACIÓN
window.navTo = function(sectionId) {
    console.log("Navegando a:", sectionId);

    // 1. OCULTAR TODO
    // Buscamos todas las secciones y las ocultamos
    document.querySelectorAll('.app-section').forEach(el => {
        el.classList.add('hidden-section');
        el.classList.add('d-none'); // Refuerzo de Bootstrap
    });
    
    // 2. MOSTRAR LA SECCIÓN ELEGIDA
    const target = document.getElementById(sectionId);
    if (target) {
        target.classList.remove('hidden-section');
        target.classList.remove('d-none');
    } else {
        console.error("No existe la sección con ID:", sectionId);
        return;
    }

    // 3. ACTUALIZAR MENÚ LATERAL (Visual)
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    // (Opcional: aquí podrías buscar el link y ponerle active, pero no es crítico)

    // 4. CARGAR DATOS ESPECÍFICOS (El paso que faltaba) 🧠
    switch(sectionId) {
        case 'dashboard':
            if(window.cargarListaPerfiles) window.cargarListaPerfiles(); 
            break;
            
        case 'perfiles':
            if(window.cargarListaPerfiles) window.cargarListaPerfiles();
            break;

        case 'transversales':
            // ¡AQUÍ ESTÁ LA CONEXIÓN! Llamamos a la función de perfiles_v2.js
            if(window.cargarCompetenciasTransversales) {
                window.cargarCompetenciasTransversales();
            } else {
                console.error("Error: No encuentro la función cargarCompetenciasTransversales.");
            }
            break;

        case 'actividades':
            // Aseguramos que el select de perfiles esté lleno
            cargarSelectPerfiles('actividadSelectPerfil');
            break;
            
        case 'matriz':
            cargarSelectPerfiles('matrizSelectPerfil');
            break;
            
        case 'malla':
            cargarSelectPerfiles('mallaSelectPerfil');
            break;
    }
};

// Función auxiliar para llenar los <select> de perfiles en otras vistas
async function cargarSelectPerfiles(selectId) {
    const select = document.getElementById(selectId);
    if(!select || select.options.length > 1) return; // Si ya tiene datos, no recargamos

    try {
        const res = await fetch('/api/profiles');
        const perfiles = await res.json();
        
        // Limpiar manteniendo el primer option
        select.innerHTML = '<option value="">-- Seleccionar --</option>';
        
        perfiles.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.innerText = p.name;
            select.appendChild(opt);
        });
    } catch(e) { console.error("Error cargando select:", e); }
}

window.logout = function() {
    localStorage.clear();
    window.location.href = 'index.html'; // Ajusta si tu login es otra ruta
};