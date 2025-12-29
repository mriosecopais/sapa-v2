<?php
namespace App\Controllers;

use App\Config\Database;

class InstrumentController {
    private $pdo;

    public function __construct() {
        // Usamos la conexión que ya sabemos que funciona
        $this->pdo = Database::getInstance()->getConnection();
    }

    // GET /api/instruments
    public function index() {
        try {
            // Traemos el nombre del perfil también para mostrarlo bonito
            $sql = "SELECT i.*, p.name as profile_name 
                    FROM instruments i 
                    JOIN profiles p ON i.profile_id = p.id 
                    ORDER BY i.id DESC";
            $stmt = $this->pdo->query($sql);
            echo json_encode($stmt->fetchAll());
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // POST /api/instruments
    public function store() {
        // Leemos los datos que envía el Frontend
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validación básica
        if (empty($data['title']) || empty($data['profile_id'])) {
            http_response_code(400); 
            echo json_encode(['error' => 'Falta el título o el perfil']); 
            return;
        }

        try {
            $sql = "INSERT INTO instruments (profile_id, title, description, type, status) 
                    VALUES (:pid, :title, :desc, :type, 'draft')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':pid' => $data['profile_id'],
                ':title' => $data['title'],
                ':desc' => $data['description'] ?? 'Encuesta de percepción',
                ':type' => 'survey'
            ]);
            
            echo json_encode([
                'id' => $this->pdo->lastInsertId(), 
                'message' => 'Instrumento creado exitosamente'
            ]);
        } catch (\Exception $e) {
            http_response_code(500); 
            echo json_encode(['error' => 'Error SQL: ' . $e->getMessage()]);
        }
    }

    // GET /api/instruments/{id}
    public function show($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM instruments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $instrument = $stmt->fetch();

        if (!$instrument) {
            http_response_code(404); echo json_encode(['error' => 'No encontrado']); return;
        }

        // Cargar preguntas
        $stmtC = $this->pdo->prepare("SELECT * FROM criteria WHERE instrument_id = :id");
        $stmtC->execute([':id' => $id]);
        $instrument['criteria'] = $stmtC->fetchAll();

        echo json_encode($instrument);
    }

    // POST /api/instruments/add-competencies
    public function addCompetencies() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        try {
            foreach ($data['competency_ids'] as $compId) {
                // Buscar descripción original
                $stmt = $this->pdo->prepare("SELECT description FROM competencies WHERE id = ?");
                $stmt->execute([$compId]);
                $row = $stmt->fetch();

                if ($row) {
                    // Insertar pregunta
                    $ins = $this->pdo->prepare("INSERT INTO criteria (instrument_id, competency_id, description, max_score) VALUES (?, ?, ?, 4)");
                    $ins->execute([$data['instrument_id'], $compId, $row['description']]);
                }
            }
            echo json_encode(['message' => 'Ok']);
        } catch (\Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // POST /api/instruments/publish
    public function publish() {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->pdo->prepare("UPDATE instruments SET status = 'published' WHERE id = ?")->execute([$data['id']]);
        echo json_encode(['message' => 'Publicado']);
    }

    // POST /api/instruments/save-ai
    // Recibe el JSON completo de la IA y lo guarda en una sola transacción
    public function saveFromAI() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['profile_id']) || empty($data['criteria'])) {
            http_response_code(400); echo json_encode(['error' => 'Faltan datos']); return;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Crear el Instrumento (Cabecera)
            $sqlInst = "INSERT INTO instruments (profile_id, title, description, type, status) 
                        VALUES (?, ?, ?, 'rubric', 'draft')";
            $stmt = $this->pdo->prepare($sqlInst);
            $stmt->execute([
                $data['profile_id'], 
                $data['title'], 
                $data['description']
            ]);
            $instId = $this->pdo->lastInsertId();

            // 2. Insertar los Criterios
            $sqlCrit = "INSERT INTO criteria (instrument_id, description, weight, max_score) VALUES (?, ?, ?, 7)";
            $stmtCrit = $this->pdo->prepare($sqlCrit);

            foreach ($data['criteria'] as $crit) {
                // Limpiamos el peso (quitamos el %)
                $weight = str_replace('%', '', $crit['weight']);
                $stmtCrit->execute([$instId, $crit['description'], $weight]);
            }

            $this->pdo->commit();
            echo json_encode(['message' => 'Rúbrica guardada exitosamente', 'id' => $instId]);

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
        }
    }

}