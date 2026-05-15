<?php

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Logger;

class WasenderService
{
    private const API_URL = 'https://wasenderapi.com/api/send-message';

    private string $apiToken;

    public function __construct(Config $config)
    {
        $this->apiToken = $config->get('wasender.api_token', '');

        $this->logWasender('wasender_api_token_loaded', [
            'isConfigured' => $this->apiToken !== '',
            'tokenPreview' => $this->maskToken($this->apiToken),
        ]);
    }

    public function sendDocument(string $to, string $documentUrl, string $fileName, string $textWA): array
    {
        $this->logWasender('send_document_started', [
            'to' => $this->maskPhoneNumber($to),
            'documentUrl' => $documentUrl,
            'fileName' => $fileName,
        ]);

        if ($this->apiToken === '') {
            $this->logWasender('send_document_missing_api_token');
            throw new ApiException('Wasender API token is not configured.', 500);
        }

        $payload = [
            'to' => $to,
            'documentUrl' => $documentUrl,
            'fileName' => $fileName,
            'text' => $textWA,
        ];

        $response = $this->makeRequest($payload);

        if (!isset($response['success']) || $response['success'] !== true) {
            $errorMessage = $this->buildRequestFailureMessage($response);
            $this->logWasender('send_document_failed', [
                'to' => $this->maskPhoneNumber($to),
                'response' => $response,
            ]);
            throw new ApiException($errorMessage, 500, [
                'wasender' => $response,
            ]);
        }

        $response['waSentTime'] = date('Y-m-d H:i:s');
        $this->logWasender('send_document_succeeded', [
            'to' => $this->maskPhoneNumber($to),
            'waSentTime' => $response['waSentTime'],
            'success' => $response['success'] ?? null,
        ]);

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

        $this->logWasender('send_document_request_sent', [
            'to' => $this->maskPhoneNumber((string) ($payload['to'] ?? '')),
            'documentUrl' => $payload['documentUrl'] ?? null,
            'fileName' => $payload['fileName'] ?? null,
            'tokenPreview' => $this->maskToken($this->apiToken),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($error) {
            $this->logWasender('send_document_curl_error', [
                'to' => $this->maskPhoneNumber((string) ($payload['to'] ?? '')),
                'error' => $error,
            ]);
            throw new ApiException('Wasender API request failed: ' . $error, 500);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logWasender('send_document_invalid_json', [
                'to' => $this->maskPhoneNumber((string) ($payload['to'] ?? '')),
                'httpCode' => $httpCode,
                'response' => $response,
            ]);
            throw new ApiException('Invalid response from Wasender API.', 500);
        }

        if (in_array($httpCode, [401, 403], true)) {
            $this->logWasender('send_document_unauthorized', [
                'to' => $this->maskPhoneNumber((string) ($payload['to'] ?? '')),
                'httpCode' => $httpCode,
                'response' => $decoded,
            ]);
            throw new ApiException('Wasender API token is invalid or unauthorized.', 500, [
                'wasender' => $decoded,
            ]);
        }

        $this->logWasender('send_document_response_received', [
            'to' => $this->maskPhoneNumber((string) ($payload['to'] ?? '')),
            'httpCode' => $httpCode,
            'success' => $decoded['success'] ?? null,
        ]);

        return $decoded ?? [];
    }

    private function logWasender(string $event, array $context = []): void
    {
        Logger::info('whatsapp', '[WasenderService] ' . $event, $context);
    }

    private function buildRequestFailureMessage(array $response): string
    {
        $message = trim((string) ($response['message'] ?? $response['error'] ?? ''));

        if ($message !== '') {
            return 'Failed to send WhatsApp message: ' . $message;
        }

        return 'Failed to send WhatsApp message.';
    }

    private function maskToken(string $token): string
    {
        $token = trim($token);

        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 8) {
            return substr($token, 0, 2) . str_repeat('*', max(strlen($token) - 2, 0));
        }

        return substr($token, 0, 4) . str_repeat('*', max(strlen($token) - 8, 0)) . substr($token, -4);
    }

    private function maskPhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if ($digits === null || $digits === '') {
            return '';
        }

        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }

        return substr($digits, 0, 2) . str_repeat('*', max(strlen($digits) - 4, 0)) . substr($digits, -2);
    }
}
