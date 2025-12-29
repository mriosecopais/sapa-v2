<?php
// 1. INICIAR SESIÓN
session_start();

// DEBUG: Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. CONFIGURACIÓN DE CORS (CORREGIDA PARA SESIONES)
// Detectamos quién hace la petición para responderle dinámicamente
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin"); 
header("Access-Control-Allow-Credentials: true"); // <--- ESTO ES VITAL PARA QUE LA SESIÓN NO SE PIERDA
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. CARGADOR MANUAL DE CLASES (REEMPLAZO DE COMPOSER)
// Este bloque busca automáticamente los archivos en la carpeta /src
spl_autoload_register(function ($class) {
    // Prefijo del proyecto
    $prefix = 'App\\';
    
    // Carpeta base donde está el código fuente (../src/)
    $base_dir = __DIR__ . '/../src/';

    // Verificar si la clase usa nuestro prefijo
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener el nombre relativo de la clase
    $relative_class = substr($class, $len);

    // Reemplazar las barras invertidas de namespace por barras de directorios
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe, cargarlo
    if (file_exists($file)) {
        require $file;
    }
});

// 4. IMPORTAR CLASES NECESARIAS
use App\Core\Router;
use App\Controllers\ProfileController;
use App\Controllers\CompetencyController;
use App\Controllers\ActivityController;
use App\Controllers\MallaController;
use App\Controllers\StudentController;
use App\Controllers\GradeController;
use App\Controllers\AiController;
use App\Controllers\InstrumentController;
use App\Controllers\AuthController;

// 5. INICIALIZAR EL ROUTER
$router = new Router();

// --- ZONA DE RUTAS ---

// A. Autenticación (Login)
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me', [AuthController::class, 'me']);

// B. Perfiles
$router->get('/api/profiles', [ProfileController::class, 'index']);
$router->get('/api/profiles/{id}', [ProfileController::class, 'show']);
$router->post('/api/profiles', [ProfileController::class, 'store']);
$router->put('/api/profiles/{id}', [ProfileController::class, 'update']);
$router->delete('/api/profiles/{id}', [ProfileController::class, 'destroy']);

// C. Competencias
$router->get('/api/profiles/{id}/competencies', [ProfileController::class, 'competencies']);
$router->get('/api/competencies', [CompetencyController::class, 'index']);
$router->get('/api/competencies/{id}', [CompetencyController::class, 'show']);      // <-- NUEVA
$router->post('/api/competencies', [CompetencyController::class, 'store']);
$router->put('/api/competencies/{id}', [CompetencyController::class, 'update']);    // <-- NUEVA
$router->delete('/api/competencies/{id}', [CompetencyController::class, 'destroy']);

// D. Malla y Actividades
$router->post('/api/activities', [ActivityController::class, 'store']);
$router->delete('/api/activities/{id}', [ActivityController::class, 'destroy']);
$router->get('/api/profiles/{id}/malla', [MallaController::class, 'getGraph']);

// ... imports ...
use App\Controllers\MatrixController; 

// ...

// I. Matriz de Tributación
$router->get('/api/matrix/{id}', [MatrixController::class, 'show']);
$router->post('/api/matrix/toggle', [MatrixController::class, 'toggle']);

// E. Estudiantes
$router->get('/api/students', [StudentController::class, 'index']);
$router->post('/api/students', [StudentController::class, 'store']);
$router->get('/api/students/{id}', [StudentController::class, 'show']);
$router->put('/api/students/{id}', [StudentController::class, 'update']);
$router->delete('/api/students/{id}', [StudentController::class, 'destroy']);

// F. Notas
$router->post('/api/grades', [GradeController::class, 'store']);
$router->get('/api/students/{id}/grades', [GradeController::class, 'index']);

// G. Inteligencia Artificial
$router->post('/api/ai/generate', [AiController::class, 'generate']);
$router->post('/api/ai/analyze', [App\Controllers\AiController::class, 'analyze']);

// H. Instrumentos (Rúbricas)
$router->get('/api/instruments/{id}', [InstrumentController::class, 'show']);
$router->post('/api/instruments/ai-generate', [InstrumentController::class, 'generateFromAI']);
$router->post('/api/instruments/add-competencies', [InstrumentController::class, 'addCompetencies']);
$router->post('/api/instruments/publish', [InstrumentController::class, 'publish']);
$router->get('/api/instruments', [InstrumentController::class, 'index']); // <--- ESTA FALTABA
$router->post('/api/instruments', [InstrumentController::class, 'store']); // <--- ESTA FALTABA
$router->post('/api/instruments/save-ai', [InstrumentController::class, 'saveFromAI']);

// ... imports
use App\Controllers\EnrollmentController;

// J. Gestión Académica (Inscripciones)
$router->get('/api/enrollments/student/{id}', [EnrollmentController::class, 'byStudent']);
$router->post('/api/enrollments', [EnrollmentController::class, 'store']);
$router->delete('/api/enrollments/{id}', [EnrollmentController::class, 'destroy']);

// ... imports
use App\Controllers\EvaluationController; // <--- Importar arriba

// L. Evaluaciones
$router->post('/api/evaluations', [EvaluationController::class, 'store']);

// Rutas de Actividades
$router->get('/api/activities', [ActivityController::class, 'index']);
$router->get('/api/activities/{id}/full', [ActivityController::class, 'getFull']);
$router->post('/api/activities/save-general', [ActivityController::class, 'saveGeneral']);

use App\Controllers\ReportController;

// M. Reportes y Analítica
$router->get('/api/reports/gap/{id}', [ReportController::class, 'gapAnalysis']);

use App\Controllers\UploadController;

// N. Evidencias
$router->post('/api/upload/evidence', [UploadController::class, 'upload']);

// 6. EJECUTAR EL ROUTER
$router->dispatch();