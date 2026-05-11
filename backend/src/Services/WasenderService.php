<?php

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;

class WasenderService
{
    private const API_URL = 'https://www.wasenderapi.com/api/send-message';

    private string $apiToken;

    public function __construct(Config $config)
    {
        $this->apiToken = $config->get('wasender.api_token', '');
    }

    public function sendDocument(string $to, string $documentUrl, string $fileName): array
    {
        if ($this->apiToken === '') {
            throw new ApiException('Wasender API token is not configured.', 500);
        }

        $payload = [
            'to' => $to,
            'documentUrl' => $documentUrl,
            'fileName' => $fileName,
        ];

        $response = $this->makeRequest($payload);

        if (!isset($response['status']) || $response['status'] !== 'success') {
            throw new ApiException('Failed to send WhatsApp message.', 500, [
                'wasender' => $response,
            ]);
        }

        return $response;
    }

    private function makeRequest(array $payload): array
    {
        $ch = curl_init(self::API_URL);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiToken,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new ApiException('Wasender API request failed: ' . $error, 500);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid response from Wasender API.', 500);
        }

        return $decoded ?? [];
    }
}