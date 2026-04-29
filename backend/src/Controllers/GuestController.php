<?php

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Repositories\GuestRepository;
use App\Services\GuestImportService;
use App\Utils\ReservationUtil;

require_once dirname(__DIR__, 2) . '/utils/reservationUtil.php';

class GuestController
{
    public function __construct(
        private readonly GuestRepository $guests,
        private readonly GuestImportService $guestImportService,
        private readonly ReservationUtil $reservationUtil
    )
    {
    }

    public function index(Request $request): array
    {
        $guests = $this->guests->all(
            $this->nullableString($request->query('search')),
            $this->nullableString($request->query('status'))
        );

        return [
            'data' => [
                'guests' => $guests,
                'stats' => $this->buildStats($guests),
            ],
        ];
    }

    public function show(Request $request): array
    {
        $guest = $this->guests->find((int) $request->attribute('id'));

        if (!$guest) {
            throw new ApiException('Guest not found.', 404);
        }

        return ['data' => ['guest' => $guest]];
    }

    public function store(Request $request): array
    {
        $payload = $this->validatePayload($request->body(), 'active');
        $guest = $this->guests->create($payload);

        return [
            'message' => 'Guest created successfully.',
            'data' => ['guest' => $guest],
        ];
    }

    public function update(Request $request): array
    {
        $id = (int) $request->attribute('id');
        $existingGuest = $this->guests->find($id);

        if (!$existingGuest) {
            throw new ApiException('Guest not found.', 404);
        }

        $payload = $this->validatePayload($request->body(), $existingGuest['status']);
        $guest = $this->guests->update($id, $payload);

        return [
            'message' => 'Guest updated successfully.',
            'data' => ['guest' => $guest],
        ];
    }

    public function import(Request $request): array
    {
        $file = $request->file('file');

        if ($file === null) {
            throw new ApiException('Please upload your Excel file using the "file" field.', 422);
        }

        $rows = $this->guestImportService->parseUploadedFile($file);
        $imported = $this->guests->import($rows);

        return [
            'message' => 'Guest import completed successfully.',
            'data' => [
                'importedCount' => $imported,
            ],
        ];
    }

    public function destroy(Request $request): array
    {
        $id = (int) $request->attribute('id');

        if (!$this->guests->find($id)) {
            throw new ApiException('Guest not found.', 404);
        }

        $this->guests->delete($id);

        return ['message' => 'Guest deleted successfully.'];
    }

    public function checkIn(Request $request): array
    {
        $payload = $request->body();
        $code = trim((string) ($payload['qrCode'] ?? $payload['registrationNumber'] ?? ''));

        if ($code === '') {
            throw new ApiException('QR code payload is required.', 422, [
                'qrCode' => ['Scan a QR code or enter a registration number.'],
            ]);
        }

        $registrationNumber = $this->extractRegistrationNumber($code);

        if ($registrationNumber === null) {
            throw new ApiException('Unable to read a valid registration number from this QR code.', 422, [
                'qrCode' => ['The scanned QR code does not match a guest registration code.'],
            ]);
        }

        $guest = $this->guests->findByRegistrationNumber($registrationNumber);

        if (!$guest) {
            throw new ApiException('Guest not found for this registration number.', 404);
        }

        if ($guest['isCheckedIn']) {
            return [
                'message' => 'Guest has already been checked in.',
                'data' => [
                    'status' => 'already_checked_in',
                    'guest' => $guest,
                    'registrationNumber' => $registrationNumber,
                ],
            ];
        }

        $checkedInGuest = $this->guests->markCheckedIn($guest['id'], 'qr_scan');

        return [
            'message' => 'Guest checked in successfully.',
            'data' => [
                'status' => 'checked_in',
                'guest' => $checkedInGuest,
                'registrationNumber' => $registrationNumber,
            ],
        ];
    }

    public function invitationTicket(Request $request): ?array
    {
        $guest = $this->guests->find((int) $request->attribute('id'));

        if (!$guest) {
            throw new ApiException('Guest not found.', 404);
        }

        $ticket = $this->reservationUtil->generateInvitationTicketPdf($guest);

        http_response_code(200);
        header('Content-Type: ' . $ticket['mime']);
        header('Content-Disposition: inline; filename="' . $ticket['filename'] . '"');
        header('Content-Length: ' . strlen($ticket['content']));

        echo $ticket['content'];

        return null;
    }

    private function validatePayload(array $payload, string $defaultStatus = 'active'): array
    {
        $data = [
            'fullName' => trim((string) ($payload['fullName'] ?? '')),
            'company' => trim((string) ($payload['company'] ?? '')),
            'position' => trim((string) ($payload['position'] ?? '')),
            'seatNumber' => trim((string) ($payload['seatNumber'] ?? '')),
            'checkIn' => trim((string) ($payload['checkIn'] ?? '')),
            'checkOut' => trim((string) ($payload['checkOut'] ?? '')),
            'specialRequests' => trim((string) ($payload['specialRequests'] ?? '')),
            'vipStatus' => (bool) ($payload['vipStatus'] ?? false),
            'status' => trim((string) ($payload['status'] ?? $defaultStatus)),
        ];

        $errors = [];

        foreach (['fullName', 'checkIn', 'checkOut'] as $field) {
            if ($data[$field] === '') {
                $errors[$field][] = 'This field is required.';
            }
        }

        if (!in_array($data['status'], ['active', 'pending'], true)) {
            $errors['status'][] = 'Status must be active or pending.';
        }

        if ($data['checkIn'] !== '' && $data['checkOut'] !== '' && $data['checkOut'] < $data['checkIn']) {
            $errors['checkOut'][] = 'Check-out must be on or after check-in.';
        }

        if ($errors !== []) {
            throw new ApiException('Validation failed.', 422, $errors);
        }

        return $data;
    }

    private function buildStats(array $guests): array
    {
        return [
            'totalGuests' => count($guests),
            'activeGuests' => count(array_filter($guests, fn ($guest) => $guest['status'] === 'active')),
            'pendingGuests' => count(array_filter($guests, fn ($guest) => $guest['status'] === 'pending')),
            'vipGuests' => count(array_filter($guests, fn ($guest) => $guest['vipStatus'] === true)),
            'checkedInGuests' => count(array_filter($guests, fn ($guest) => $guest['isCheckedIn'] === true)),
            'awaitingCheckInGuests' => count(array_filter($guests, fn ($guest) => $guest['isCheckedIn'] === false)),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function extractRegistrationNumber(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            foreach (['registrationNumber', 'registration_number', 'code'] as $key) {
                $candidate = trim((string) ($decoded[$key] ?? ''));
                if ($candidate !== '') {
                    return strtoupper($candidate);
                }
            }
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $query = [];
            parse_str((string) parse_url($value, PHP_URL_QUERY), $query);

            foreach (['registrationNumber', 'registration_number', 'code'] as $key) {
                $candidate = trim((string) ($query[$key] ?? ''));
                if ($candidate !== '') {
                    return strtoupper($candidate);
                }
            }
        }

        if (preg_match('/PANIN_[A-Z0-9_]+/i', $value, $matches) === 1) {
            return strtoupper($matches[0]);
        }

        return null;
    }
}
