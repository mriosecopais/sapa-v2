<?php
namespace App\Controllers;

use App\Models\User;

class AuthController {
    
    // POST /api/auth/login
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan credenciales']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($data['email']);

        // Verificar si usuario existe Y si la contraseña coincide
        if ($user && password_verify($data['password'], $user['password'])) {
            
            // ¡ÉXITO! Guardamos datos en la sesión del servidor
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            echo json_encode([
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Credenciales incorrectas']);
        }
    }

    // POST /api/auth/logout
    public function logout() {
        session_destroy();
        echo json_encode(['message' => 'Sesión cerrada']);
    }

    // GET /api/auth/me (Para saber quién soy si recargo la página)
    public function me() {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'id' => $_SESSION['user_id'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'No estás logueado']);
        }
    }
}