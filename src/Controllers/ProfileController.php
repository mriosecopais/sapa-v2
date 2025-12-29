<?php
namespace App\Controllers;

use App\Config\Database;

class ProfileController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function index() {
        $stmt = $this->pdo->query("SELECT * FROM profiles ORDER BY name ASC");
        echo json_encode($stmt->fetchAll());
    }

    public function show($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM profiles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            $profile['has_licentiate'] = (bool)$profile['has_licentiate'];
            echo json_encode($profile);
        } else {
            http_response_code(404); echo json_encode(['error' => 'Perfil no encontrado']);
        }
    }

    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        try {
            if (empty($data['id']) && empty($data['code'])) {
                $prefix = strtoupper(substr($data['name'], 0, 3));
                $data['code'] = $prefix . '-' . rand(100, 999);
            }

            if (!empty($data['id'])) {
                $sql = "UPDATE profiles SET code=:code, name=:name, career=:career, faculty=:faculty, description=:desc, director=:dir, duration=:dur, accreditation_years=:acc, has_licentiate=:lic WHERE id=:id";
                $params = [
                    ':id' => $data['id'], ':code' => $data['code'], ':name' => $data['name'], ':career' => $data['career'],
                    ':faculty' => $data['faculty'], ':desc' => $data['description']??'', ':dir' => $data['director']??'',
                    ':dur' => $data['duration']??0, ':acc' => $data['accreditation_years']??0, ':lic' => $data['has_licentiate']?1:0
                ];
            } else {
                $sql = "INSERT INTO profiles (code, name, career, faculty, description, director, duration, accreditation_years, has_licentiate) VALUES (:code, :name, :career, :faculty, :desc, :dir, :dur, :acc, :lic)";
                $params = [
                    ':code' => $data['code'], ':name' => $data['name'], ':career' => $data['career'],
                    ':faculty' => $data['faculty'], ':desc' => $data['description']??'', ':dir' => $data['director']??'',
                    ':dur' => $data['duration']??0, ':acc' => $data['accreditation_years']??0, ':lic' => $data['has_licentiate']?1:0
                ];
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['message' => 'Guardado exitoso']);
        } catch (\Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM profiles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['message' => 'Eliminado']);
    }

    // CORRECCIÓN: Método para obtener competencias usando 'type'
    public function competencies($id) {
        $stmtP = $this->pdo->prepare("SELECT has_licentiate FROM profiles WHERE id = :id");
        $stmtP->execute([':id' => $id]);
        $profile = $stmtP->fetch();
        $tieneLicenciatura = ($profile && intval($profile['has_licentiate']) === 1);

        // CORRECCIÓN: Usamos 'type' en lugar de 'scope'
        $sql = "SELECT * FROM competencies WHERE (type = 'sello') OR (profile_id = :pid AND type = 'disciplinar')";
        
        if ($tieneLicenciatura) {
            $sql .= " OR (type = 'licenciatura') ";
        }

        $sql .= " ORDER BY CASE 
                    WHEN type = 'sello' THEN 1 
                    WHEN type = 'licenciatura' THEN 2 
                    WHEN type = 'disciplinar' THEN 3 
                  END ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pid' => $id]);
            echo json_encode($stmt->fetchAll());
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}