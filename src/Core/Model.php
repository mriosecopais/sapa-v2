<?php
namespace App\Core;

use App\Config\Database;
use PDO;

abstract class Model {
    protected $pdo;
    protected $table;

    public function __construct() {
        // Obtiene la instancia única de la base de datos
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll() {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}