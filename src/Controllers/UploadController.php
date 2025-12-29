<?php
namespace App\Controllers;

use App\Config\Database;

class UploadController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function upload() {
        // 1. Depuración: Ver qué llega
        if (empty($_FILES['evidence'])) {
            http_response_code(400); echo json_encode(['error' => 'No llegó ningún archivo al servidor']); return;
        }
        if (empty($_POST['evaluation_id']) || $_POST['evaluation_id'] == 'undefined') {
            http_response_code(400); echo json_encode(['error' => 'ID de evaluación faltante o inválido']); return;
        }

        $evalId = $_POST['evaluation_id'];
        $file = $_FILES['evidence'];
        
        // 2. Ruta Absoluta (Ajustada para evitar problemas de rutas relativas)
        // __DIR__ es src/Controllers. Subimos 2 niveles para llegar a la raíz, luego entramos a public/uploads
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        
        // Crear carpeta si no existe
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                http_response_code(500); echo json_encode(['error' => 'No se pudo crear la carpeta uploads. Revisa permisos.']); return;
            }
        }

        // 3. Mover Archivo
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'ev_' . $evalId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            try {
                $webPath = '/uploads/' . $filename; 
                $sql = "INSERT INTO evidence_files (evaluation_id, filename, filepath, file_type) VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$evalId, $file['name'], $webPath, $ext]);

                echo json_encode(['message' => 'Guardado OK', 'path' => $webPath]);
            } catch (\Exception $e) {
                http_response_code(500); echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
            }
        } else {
            $error = error_get_last();
            http_response_code(500); echo json_encode(['error' => 'Falló move_uploaded_file. Permisos de carpeta?', 'details' => $error]);
        }
    }
}