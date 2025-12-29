<?php
namespace App\Controllers;

use App\Config\Database;

class ActivityController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // GET /api/activities?profile_id=X
    public function index() {
        $pid = $_GET['profile_id'] ?? null;
        if (!$pid) { echo json_encode([]); return; }

        try {
            $sql = "SELECT * FROM activities WHERE profile_id = ? ORDER BY semester ASC, name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$pid]);
            echo json_encode($stmt->fetchAll());
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error SQL: " . $e->getMessage()]);
        }
    }

    // GET /api/activities/{id}/full
    public function getFull($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM activities WHERE id = ?");
        $stmt->execute([$id]);
        $activity = $stmt->fetch();

        if (!$activity) { 
            http_response_code(404); 
            echo json_encode(['error' => 'No encontrada']); 
            return; 
        }
        echo json_encode($activity);
    }

    // POST /api/activities (Crear o Editar)
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validación básica
        if (empty($data['name'])) {
            http_response_code(400); echo json_encode(['error' => 'El nombre es obligatorio']); return;
        }

        try {
            if (!empty($data['id'])) {
                // --- UPDATE ---
                $sql = "UPDATE activities SET 
                        name = :name, 
                        credits = :credits, 
                        semester = :semester,
                        custom_id = :custom_id,
                        hours_total = :ht, 
                        hours_teaching = :hd, 
                        hours_autonomous = :ha,
                        req_attendance = :ra, 
                        prerequisites = :pre
                        WHERE id = :id";
                
                $params = [
                    ':id' => $data['id'],
                    ':name' => $data['name'],
                    ':credits' => $data['credits'],
                    ':semester' => $data['semester'],
                    ':custom_id' => $data['custom_id'] ?? null,
                    ':ht' => $data['hours_total'] ?? 0,
                    ':hd' => $data['hours_teaching'] ?? 0,
                    ':ha' => $data['hours_autonomous'] ?? 0,
                    ':ra' => $data['req_attendance'] ?? 0,
                    ':pre' => $data['prerequisites'] ?? 0
                ];
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['message' => 'Actividad actualizada']);

            } else {
                // --- INSERT ---
                $sql = "INSERT INTO activities 
                        (profile_id, name, credits, semester, custom_id, hours_total, hours_teaching, hours_autonomous, req_attendance, prerequisites) 
                        VALUES 
                        (:pid, :name, :credits, :sem, :cid, :ht, :hd, :ha, :ra, :pre)";
                
                $params = [
                    ':pid' => $data['profile_id'],
                    ':name' => $data['name'],
                    ':credits' => $data['credits'],
                    ':sem' => $data['semester'],
                    ':cid' => $data['custom_id'] ?? null,
                    ':ht' => $data['hours_total'] ?? 0,
                    ':hd' => $data['hours_teaching'] ?? 0,
                    ':ha' => $data['hours_autonomous'] ?? 0,
                    ':ra' => $data['req_attendance'] ?? 0,
                    ':pre' => $data['prerequisites'] ?? 0
                ];
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['message' => 'Actividad creada', 'id' => $this->pdo->lastInsertId()]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // DELETE /api/activities/{id}
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM activities WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['message' => 'Actividad eliminada']);
    }
}