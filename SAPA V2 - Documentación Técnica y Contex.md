SAPA V2 - Documentación Técnica y Contexto del Proyecto
1. Descripción General
SAPA V2 es un Sistema de Gestión Curricular y Acreditación desarrollado en PHP Nativo. Su objetivo es gestionar el ciclo de vida académico: desde la definición del Perfil de Egreso, pasando por la Matriz de Tributación, hasta la Evaluación de Competencias y la generación de Reportes de Triangulación (Realidad vs. Percepción) asistidos por Inteligencia Artificial.

2. Stack Tecnológico
Infraestructura: Docker (Nginx + PHP 8.3 FPM + MySQL 8.0 + PhpMyAdmin).

Backend: PHP 8.3 Nativo (Sin frameworks pesados). Arquitectura MVC estricta, Orientada a Objetos.

Frontend: SPA (Single Page Application) simulada en app.html. Uso de Bootstrap 5 y Vanilla JS (Fetch API).

Base de Datos: MySQL 8.0 con integridad referencial (InnoDB).

IA: Integración con OpenAI (GPT-3.5/4) para generación de rúbricas y análisis de reportes.

3. Estructura de Archivos (Actualizada)
Esta es la estructura operativa del proyecto. Cualquier modificación debe respetar esta jerarquía.

Plaintext

/public                      # WEB ROOT (Nginx apunta aquí)
    /uploads                 # Repositorio físico de evidencias (PDF/DOCX/IMG)
    app.html                 # Frontend Principal (SPA - Dashboard, Gestión, Evaluaciones)
    index.php                # Front Controller (Router y manejo de CORS)
    login.html               # Interfaz de Acceso
    test.html                # (Deprecado/Legacy)
    listar.php               # Utilidad de diagnóstico

/src                         # NÚCLEO DE LA APLICACIÓN
    /Config
        Database.php         # Singleton PDO conexión a BD
    /Controllers             # Lógica de Endpoints (API REST)
        ActivityController.php   # Asignaturas
        AiController.php         # Puente con OpenAI
        AuthController.php       # Login/Logout
        CompetencyController.php # Competencias (Sello/Disciplinar)
        EnrollmentController.php # Inscripciones (Alumno <-> Asignatura)
        EvaluationController.php # Guardado de notas y detalles
        GradeController.php      # (Legacy - Notas simples)
        InstrumentController.php # Encuestas y Rúbricas
        MallaController.php      # Visualización curricular
        MatrixController.php     # Matriz de Tributación (Cruces)
        ProfileController.php    # Carreras/Perfiles
        ReportController.php     # Triangulación y Brechas
        StudentController.php    # Gestión de alumnos
        UploadController.php     # Subida de archivos (Evidencias)
    /Core
        Model.php            # CRUD Base
        Router.php           # Enrutador personalizado
    /Middleware              # (Reservado)
    /Models                  # Mapeo de Tablas
        Activity.php, AiConfig.php, Competency.php, Criterion.php,
        Grade.php, Instrument.php, Profile.php, Student.php, User.php
    /Services
        AiService.php        # Lógica de prompts (Legacy)
4. Modelo de Datos (Base de Datos)
El sistema se basa en la interconexión de estas entidades clave:

Perfiles (profiles): Carreras (Ej: Ingeniería, Enfermería).

Competencias (competencies):

scope: 'specific' (De la carrera) o 'seal' (Sello institucional).

type: Disciplinar, Sello, Licenciatura.

Actividades (activities): Asignaturas de la malla.

Matriz (activity_competency): Tabla pivote que define qué asignatura tributa a qué competencia y en qué nivel (low, medium, high).

Usuarios y Alumnos:

users: Acceso al sistema (Roles: admin, professor, manager).

students: Datos académicos.

enrollments: Relación Alumno <-> Asignatura (Año/Semestre).

Instrumentos (instruments):

type: 'rubric' (Evaluación docente) o 'survey' (Encuesta estudiante).

criteria: Las preguntas o criterios asociados a una competencia específica (competency_id).

Evaluación (evaluations):

Cabecera con nota final.

evaluation_details: Puntaje desglosado por criterio.

evidence_files: Archivos adjuntos (evidencia física).

5. Lógica de Negocio y Flujos Implementados
A. Gestión Curricular
Perfiles: CRUD completo.

Competencias Sello: Biblioteca transversal visible por todos los perfiles.

Matriz de Tributación: Interfaz visual (Tabla) para cruzar Asignaturas vs. Competencias.

B. Gestión Académica
Inscripción: El sistema sabe qué materias cursa cada alumno (EnrollmentController). Esto permite personalizar las encuestas.

C. Evaluación e Instrumentos
IA Generativa: AiController genera rúbricas JSON completas a partir de un prompt de texto.

Instrumentos Híbridos: Se pueden crear rúbricas manualmente o vía IA.

Sala de Evaluación: Interfaz para que el docente califique. Calcula promedio en tiempo real y permite adjuntar evidencia (UploadController).

D. Acreditación y Reportes
Encuestas de Percepción: Los alumnos responden instrumentos tipo 'survey'.

Triangulación: ReportController compara el promedio de notas docentes (Rúbrica) vs. autoevaluación (Encuesta) para una misma competencia.

Análisis IA: Un endpoint lee los datos JSON de las brechas y genera un resumen ejecutivo (Fortalezas/Debilidades).

Visor de Evidencia: El reporte incluye enlaces directos a los archivos PDF/IMG subidos como prueba.

6. Endpoints Principales (API)
GET /api/profiles: Listar carreras.

GET /api/matrix/{id}: Obtener matriz cruzada.

POST /api/matrix/toggle: Guardar relación asignatura-competencia.

POST /api/ai/generate: Generar estructura de rúbrica con GPT.

POST /api/instruments/save-ai: Guardar rúbrica generada.

POST /api/evaluations: Guardar calificación (Rubrica o Encuesta).

POST /api/upload/evidence: Subir archivo adjunto a evaluación.

GET /api/reports/gap/{id}: Obtener datos para gráfico de triangulación.

POST /api/ai/analyze: Interpretar reporte con IA.

7. Estado Actual
El sistema es funcional para el ciclo completo de acreditación ("End-to-End").

✅ Definición Curricular.

✅ Evaluación Docente + Evidencia.

✅ Encuesta Estudiante.

✅ Reporte de Brechas + Auditoría.

Instrucción para la IA: Usa este contexto para entender que el sistema no utiliza frameworks como Laravel o Symfony, sino una arquitectura PHP nativa personalizada. Al proponer código, mantén el estilo de los Controladores existentes (PDO Singleton, respuestas JSON estrictas).