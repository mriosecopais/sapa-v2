<?php
namespace App\Controllers;

use App\Config\Database;

class CompetencyController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // GET /api/competencies?scope=seal|licensure
    public function index() {
        $scope = $_GET['scope'] ?? null;
        $sql = "SELECT * FROM competencies";
        $params = [];

        if ($scope) {
            $typeDB = 'disciplinar';
            if ($scope === 'seal') $typeDB = 'sello';
            if ($scope === 'licensure') $typeDB = 'licenciatura';

            $sql .= " WHERE type = :type";
            $params[':type'] = $typeDB;
        }

        $sql .= " ORDER BY id DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll());
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error SQL: " . $e->getMessage()]);
        }
    }

    // GET /api/competencies/{id}
    public function show($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM competencies WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $comp = $stmt->fetch();
            
            if (!$comp) {
                http_response_code(404);
                echo json_encode(['error' => 'Competencia no encontrada']);
                return;
            }
            
            // Traducir type a scope para el frontend
            $scope = 'specific';
            if ($comp['type'] === 'sello') $scope = 'seal';
            if ($comp['type'] === 'licenciatura') $scope = 'licensure';
            $comp['scope'] = $scope;
            
            echo json_encode($comp);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    // POST /api/competencies
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(empty($data['description'])) {
            http_response_code(400); 
            echo json_encode(['error' => 'Descripción obligatoria']); 
            return;
        }

        $scopeIn = $data['scope'] ?? 'specific';
        $typeDB = 'disciplinar';
        
        if ($scopeIn === 'seal') $typeDB = 'sello';
        if ($scopeIn === 'licensure') $typeDB = 'licenciatura';

        $customId = $data['custom_id'] ?? null;
        if(!$customId) {
            $prefix = strtoupper(substr($typeDB, 0, 3)); 
            $customId = $prefix . '-' . rand(1000, 9999);
        }

        try {
            $sql = "INSERT INTO competencies (profile_id, description, type, custom_id, level_1, level_2, level_3) 
                    VALUES (:pid, :desc, :type, :cid, :l1, :l2, :l3)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':pid' => $data['profile_id'] ?? null,
                ':desc' => $data['description'],
                ':type' => $typeDB, 
                ':cid' => $customId,
                ':l1' => $data['level_1'] ?? '',
                ':l2' => $data['level_2'] ?? '',
                ':l3' => $data['level_3'] ?? ''
            ]);
            
            echo json_encode(['message' => 'Competencia guardada', 'id' => $this->pdo->lastInsertId()]);
        } catch (\Exception $e) {
            http_response_code(500); 
            echo json_encode(['error' => 'Error DB: ' . $e->getMessage()]);
        }
    }

    // PUT /api/competencies/{id}
    public function update($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(empty($data['description'])) {
            http_response_code(400); 
            echo json_encode(['error' => 'Descripción obligatoria']); 
            return;
        }

        $scopeIn = $data['scope'] ?? 'specific';
        $typeDB = 'disciplinar';
        
        if ($scopeIn === 'seal') $typeDB = 'sello';
        if ($scopeIn === 'licensure') $typeDB = 'licenciatura';

        try {
            $sql = "UPDATE competencies SET 
                        description = :desc, 
                        type = :type, 
                        level_1 = :l1, 
                        level_2 = :l2, 
                        level_3 = :l3 
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':desc' => $data['description'],
                ':type' => $typeDB,
                ':l1' => $data['level_1'] ?? '',
                ':l2' => $data['level_2'] ?? '',
                ':l3' => $data['level_3'] ?? '',
                ':id' => $id
            ]);
            
            echo json_encode(['message' => 'Competencia actualizada', 'id' => $id]);
        } catch (\Exception $e) {
            http_response_code(500); 
            echo json_encode(['error' => 'Error DB: ' . $e->getMessage()]);
        }
    }

    // DELETE /api/competencies/{id}
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM competencies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['message' => 'Competencia eliminada']);
    }
}