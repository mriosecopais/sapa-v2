<?php
namespace App\Services;

use App\Models\AiConfig;

class AiService {
    private $configModel;
    private $apiKey;
    private $modelName;

    public function __construct() {
        $this->configModel = new AiConfig();
        $config = $this->configModel->getActiveProvider();

        if (!$config) {
            throw new \Exception("No hay configuración de IA activa en el sistema.");
        }

        $this->apiKey = $config['api_key'];
        $this->modelName = $config['model'] ?? 'gpt-3.5-turbo';
    }

    public function ask($prompt, $systemRole = "Eres un asistente académico útil.") {
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => $this->modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemRole],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('Error de conexión con IA: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $decoded = json_decode($response, true);

        if (isset($decoded['error'])) {
            throw new \Exception('Error de API IA: ' . $decoded['error']['message']);
        }

        return $decoded['choices'][0]['message']['content'] ?? "Sin respuesta.";
    }
}