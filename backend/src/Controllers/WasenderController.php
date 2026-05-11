<?php

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Services\WasenderService;

class WasenderController
{
    public function __construct(
        private readonly WasenderService $wasender
    ) {
    }

    public function sendDocument(Request $request): array
    {
        $payload = $request->body();

        $to = trim((string) ($payload['to'] ?? ''));
        $documentUrl = trim((string) ($payload['documentUrl'] ?? ''));
        $fileName = trim((string) ($payload['fileName'] ?? ''));

        if ($to === '') {
            throw new ApiException('Recipient phone number is required.', 422, [
                'to' => ['This field is required.'],
            ]);
        }

        if ($documentUrl === '') {
            throw new ApiException('Document URL is required.', 422, [
                'documentUrl' => ['This field is required.'],
            ]);
        }

        if ($fileName === '') {
            throw new ApiException('File name is required.', 422, [
                'fileName' => ['This field is required.'],
            ]);
        }

        $result = $this->wasender->sendDocument($to, $documentUrl, $fileName);

        return [
            'message' => 'Document sent successfully.',
            'data' => $result,
        ];
    }
}