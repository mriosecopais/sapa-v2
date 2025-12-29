<?php
namespace App\Controllers;

use App\Config\Database;

class AiController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // POST /api/ai/generate
    public function generate() {
        $input = json_decode(file_get_contents("php://input"), true);
        $userPrompt = $input['competency_text'] ?? '';
        
        if(empty($userPrompt)) {
            http_response_code(400); echo json_encode(['error' => 'Falta el texto de la competencia']); return;
        }

        // 1. Obtener API KEY de la base de datos
        $stmt = $this->pdo->query("SELECT api_key, model FROM ai_configs WHERE is_active = 1 LIMIT 1");
        $config = $stmt->fetch();

        if (!$config) {
            http_response_code(500); echo json_encode(['error' => 'No hay API Key configurada en la tabla ai_configs']); return;
        }

        $apiKey = $config['api_key'];
        $model = $config['model'] ?? 'gpt-3.5-turbo';

        // 2. Preparar el Prompt de Ingeniería para que devuelva JSON estricto
        $systemPrompt = "Eres un experto en evaluación curricular. Tu tarea es generar una rúbrica de evaluación basada en una competencia. 
        DEBES responder ÚNICAMENTE con un objeto JSON válido (sin markdown, sin texto extra).
        Estructura requerida:
        {
            'title': 'Título sugerido para el instrumento',
            'description': 'Breve descripción del contexto',
            'criteria': [
                { 'description': 'Descripción del criterio 1', 'weight': 25 },
                { 'description': 'Descripción del criterio 2', 'weight': 25 },
                ...
            ]
        }";

        // 3. Llamada a OpenAI
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Competencia a evaluar: " . $userPrompt]
            ],
            'temperature' => 0.7
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            http_response_code(500); echo json_encode(['error' => 'Error Curl: ' . curl_error($ch)]); return;
        }
        curl_close($ch);

        // 4. Procesar respuesta
        $jsonResponse = json_decode($response, true);
        
        if (isset($jsonResponse['error'])) {
            http_response_code(500); echo json_encode(['error' => 'OpenAI Error: ' . $jsonResponse['error']['message']]); return;
        }

        // Extraer el contenido del mensaje
        $content = $jsonResponse['choices'][0]['message']['content'];
        
        // Limpiar si GPT puso bloques de código markdown (```json ... ```)
        $content = str_replace(['```json', '```'], '', $content);
        
        // Devolver JSON limpio al frontend
        echo $content;
    }

    // POST /api/ai/analyze
    public function analyze() {
        $input = json_decode(file_get_contents("php://input"), true);
        $dataContext = $input['data'] ?? '';
        
        // Obtener Key (reutilizar código)
        $stmt = $this->pdo->query("SELECT api_key, model FROM ai_configs WHERE is_active = 1 LIMIT 1");
        $config = $stmt->fetch();
        $apiKey = $config['api_key'];

        $prompt = "Eres un consultor de acreditación universitaria. Analiza estos datos de brechas (Nota Real vs Percepción) y da un diagnóstico breve:\n" . $dataContext;

        $payload = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);

        $response = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($response, true);
        $content = $json['choices'][0]['message']['content'] ?? 'Error IA';

        echo json_encode(['analysis' => $content]);
    }

}