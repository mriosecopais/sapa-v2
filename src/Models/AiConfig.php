<?php
namespace App\Models;

use App\Core\Model;

class AiConfig extends Model {
    protected $table = 'ai_configs';

    public function getActiveProvider() {
        // Busca el primer proveedor marcado como activo
        $sql = "SELECT * FROM ai_configs WHERE is_active = 1 LIMIT 1";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch();
    }
}