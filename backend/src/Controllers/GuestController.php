<?php

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;
use App\Repositories\GuestRepository;
use App\Services\GuestImportService;
use App\Services\WasenderService;
use App\Utils\ReservationUtil;

require_once dirname(__DIR__, 2) . '/utils/reservationUtil.php';

class GuestController
{
    public function __construct(
        private readonly GuestRepository $guests,
        private readonly GuestImportService $guestImportService,
        private readonly ReservationUtil $reservationUtil,
        private readonly WasenderService $wasender,
        private readonly Config $config
    ) {
    }

    public function sendInvitation(Request $request): array
    {
        $this->assertCanManageGuests($request);

        $id = (int) $request->attribute('id');
        $this->logSendInvitation('send_invitation_started', [
            'guestId' => $id,
        ]);

        $guest = $this->guests->find($id);

        if (!$guest) {
            $this->logSendInvitation('send_invitation_guest_not_found', [
                'guestId' => $id,
            ]);
            throw new ApiException('Guest not found.', 404);
        }

        $phoneNumber = trim((string) ($guest['phoneNumber'] ?? ''));
        $this->logSendInvitation('send_invitation_guest_loaded', [
            'guestId' => $id,
            'fullName' => $guest['fullName'] ?? null,
            'phoneNumber' => $this->maskPhoneNumber($phoneNumber),
        ]);

        if ($phoneNumber === '') {
            $this->logSendInvitation('send_invitation_missing_phone', [
                'guestId' => $id,
            ]);
            throw new ApiException('Guest does not have a phone number.', 422, [
                'phone' => ['This guest cannot receive a WhatsApp message.'],
            ]);
        }

        $appUrl = rtrim($this->config->get('app.url', ''), '/');
        $documentUrl = "{$appUrl}/api/guests/{$guest['id']}/invitation-ticket";
        $fileName = "Invitation - {$guest['fullName']}.pdf";
        $textWA = "Selamat siang Bapak/Ibu {$guest['fullName']},

Kehebatan tidak hanya diukur dari angka semata, tetapi dari kualitas, komitmen, integritas, dan profesionalisme yang senantiasa dijunjung tinggi.

Karena kualitas menciptakan kepercayaan, dan ketekunan melahirkan keberhasilan, maka pada malam yang istimewa ini kami mengundang Bapak/Ibu untuk bersama-sama merayakan dedikasi dan pencapaian terbaik dari para agency force pilihan.

Dengan hormat, kami mengundang Bapak/Ibu untuk hadir pada:

Acara: Annual Awards Dinner 2026
Hari/Tanggal: Jumat, 22 Mei 2026
Lokasi: Fairmont Jakarta

*Mohon untuk membawa invitation ticket ini sebagai syarat registrasi dan akses masuk ke area acara.*

Merupakan suatu kehormatan bagi kami atas kehadiran Bapak/Ibu dalam malam apresiasi yang penuh makna ini.

Hormat kami,
PaninDai-ichiLife

CP: 0812-3456-7890 (PaninDai-ichiLife)";

        $this->logSendInvitation('send_invitation_dispatching_whatsapp', [
            'guestId' => $id,
            'documentUrl' => $documentUrl,
            'fileName' => $fileName,
            'phoneNumber' => $this->maskPhoneNumber($phoneNumber),
        ]);
        $result = $this->wasender->sendDocument($phoneNumber, $documentUrl, $fileName, $textWA);
        $this->logSendInvitation('send_invitation_whatsapp_sent', [
            'guestId' => $id,
            'waSentTime' => $result['waSentTime'] ?? null,
            'wasenderStatus' => $result['status'] ?? null,
        ]);

        $this->guests->saveWaSentTime($id, $result['waSentTime'] ?? null);
        $this->logSendInvitation('send_invitation_saved_wa_sent_time', [
            'guestId' => $id,
            'waSentTime' => $result['waSentTime'] ?? null,
        ]);

        return [
            'message' => 'Invitation sent successfully via WhatsApp.',
            'data' => $result,
        ];
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
        $this->assertCanManageGuests($request);
        $payload = $this->validatePayload($request->body(), 'active');
        $guest = $this->guests->create($payload);

        return [
            'message' => 'Guest created successfully.',
            'data' => ['guest' => $guest],
        ];
    }

    public function update(Request $request): array
    {
        $this->assertCanManageGuests($request);
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
        $this->assertCanManageGuests($request);
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
        $this->assertCanManageGuests($request);
        $id = (int) $request->attribute('id');

        if (!$this->guests->find($id)) {
            throw new ApiException('Guest not found.', 404);
        }

        $this->guests->delete($id);

        return ['message' => 'Guest deleted successfully.'];
    }

    public function checkIn(Request $request): array
    {
        $this->assertCanManageGuests($request);
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
        $this->assertCanManageGuests($request);
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
            'gaSoPosition' => trim((string) ($payload['gaSoPosition'] ?? '')),
            'seatNumber' => trim((string) ($payload['seatNumber'] ?? '')),
            'phoneNumber' => trim((string) ($payload['phoneNumber'] ?? '')),
        ];

        $errors = [];

        foreach (['fullName'] as $field) {
            if ($data[$field] === '') {
                $errors[$field][] = 'This field is required.';
            }
        }

        if ($errors !== []) {
            throw new ApiException('Validation failed.', 422, $errors);
        }

        return $data;
    }

    private function assertCanManageGuests(Request $request): void
    {
        $user = $request->attribute('authUser');
        $role = $user['role'] ?? null;

        if (!in_array($role, ['admin', 'manager'], true)) {
            throw new ApiException('You do not have permission to manage guests.', 403);
        }
    }

    private function buildStats(array $guests): array
    {
        return [
            'totalGuests' => count($guests),
            'checkedInGuests' => count(array_filter($guests, fn ($guest) => $guest['isCheckedIn'])),
            'awaitingCheckInGuests' => count(array_filter($guests, fn ($guest) => !$guest['isCheckedIn'])),
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

    private function logSendInvitation(string $event, array $context = []): void
    {
        Logger::info('whatsapp', '[GuestController] ' . $event, $context);
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
