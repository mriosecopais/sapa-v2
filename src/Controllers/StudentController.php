<?php
namespace App\Controllers;

use App\Models\Student;

class StudentController {
    private $model;

    public function __construct() {
        $this->model = new Student();
    }

    // GET /api/students
    public function index() {
        $data = $this->model->findAll();
        echo json_encode($data);
    }

    // GET /api/students/{id}
    public function show($id) {
        $student = $this->model->findById($id);
        if ($student) {
            echo json_encode($student);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Estudiante no encontrado']);
        }
    }

    // POST /api/students
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validaciones básicas
        if (empty($data['rut']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'RUT y Nombre son obligatorios']);
            return;
        }

        // 1. REGLA DE ORO: Verificar si el RUT ya existe
        $existing = $this->model->findByRut($data['rut']);
        if ($existing) {
            http_response_code(409); // 409 Conflict
            echo json_encode([
                'error' => 'El estudiante ya existe', 
                'student_id' => $existing['id'],
                'data' => $existing
            ]);
            return;
        }

        // 2. Si no existe, crear
        try {
            $id = $this->model->create($data);
            http_response_code(201);
            echo json_encode(['message' => 'Estudiante registrado', 'id' => $id]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    // PUT /api/students/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        if ($this->model->update($id, $data)) {
            echo json_encode(['message' => 'Datos actualizados']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No se pudo actualizar']);
        }
    }

    // DELETE /api/students/{id}
    public function destroy($id) {
        // OJO: Aquí la base de datos protegerá si el alumno tiene notas asociadas (Foreign Key)
        try {
            if ($this->model->delete($id)) {
                echo json_encode(['message' => 'Estudiante eliminado']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'No encontrado']);
            }
        } catch (\PDOException $e) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'No se puede eliminar: El estudiante tiene registros asociados (notas/encuestas).']);
        }
    }
}