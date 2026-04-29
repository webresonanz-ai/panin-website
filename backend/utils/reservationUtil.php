<?php

declare(strict_types=1);

namespace App\Utils;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use FPDF;
use RuntimeException;

class ReservationUtil
{
    public function generateInvitationTicketPdf(array $guest): array
    {
        $imagePath = $this->buildInvitationTicketImage($guest);

        try {
            [$imageWidth, $imageHeight] = $this->imageDimensions($imagePath);
            [$pageWidth, $pageHeight] = $this->pixelsToMillimeters($imageWidth, $imageHeight);

            $pdf = new FPDF('P', 'mm', [$pageWidth, $pageHeight]);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->AddPage();
            $pdf->Image($imagePath, 0, 0, $pageWidth, $pageHeight, 'PNG');

            $pdfBinary = $pdf->Output('S');
            if (!is_string($pdfBinary) || $pdfBinary === '') {
                throw new RuntimeException('Failed to render the invitation PDF.');
            }

            return [
                'content' => $pdfBinary,
                'filename' => $this->buildFilename($guest),
                'mime' => 'application/pdf',
            ];
        } finally {
            if (is_file($imagePath)) {
                @unlink($imagePath);
            }
        }
    }

    private function buildInvitationTicketImage(array $guest): string
    {
        if (!function_exists('imagecreatefrompng')) {
            throw new RuntimeException('PHP GD extension is required to generate invitation tickets.');
        }

        $templatePath = dirname(__DIR__) . '/templates/panin_invitation.png';
        if (!is_file($templatePath)) {
            throw new RuntimeException('Invitation template image was not found.');
        }

        $image = imagecreatefrompng($templatePath);
        if ($image === false) {
            throw new RuntimeException('Unable to load the invitation template image.');
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        $fontPath = $this->resolveFontPath();

        $darkText = imagecolorallocate($image, 31, 35, 45);
        $mutedText = imagecolorallocate($image, 94, 101, 122);

        $fullName = $this->requiredValue($guest, 'fullName');
        $company = trim((string) ($guest['company'] ?? ''));
        $position = trim((string) ($guest['position'] ?? ''));
        $seatNumber = trim((string) ($guest['seatNumber'] ?? ''));
        $checkIn = $this->formatDate((string) ($guest['checkIn'] ?? ''));
        $checkOut = $this->formatDate((string) ($guest['checkOut'] ?? ''));

        $qrImage = $this->buildQrImage($guest);
        if ($qrImage !== null) {
            $qrSize = (int) round(min($width, $height) * 0.16);
            $qrX = (int) round(($width - $qrSize) / 2);
            $qrY = (int) round($height * 0.33);
            imagecopyresampled(
                $image,
                $qrImage,
                $qrX,
                $qrY,
                0,
                0,
                $qrSize,
                $qrSize,
                imagesx($qrImage),
                imagesy($qrImage)
            );
        }

        $this->drawCenteredText($image, $fontPath, $fullName, (int) round($width * 0.028), (int) round($height * 0.72), $darkText);

        if ($company !== '') {
            $this->drawCenteredText($image, $fontPath, $company, (int) round($width * 0.017), (int) round($height * 0.765), $mutedText);
        }

        if ($position !== '') {
            $this->drawCenteredText($image, $fontPath, $position, (int) round($width * 0.015), (int) round($height * 0.8), $mutedText);
        }

        $this->drawCenteredText(
            $image,
            $fontPath,
            'Seat ' . ($seatNumber !== '' ? $seatNumber : 'Unassigned'),
            (int) round($width * 0.02),
            (int) round($height * 0.59),
            $darkText
        );

        $this->drawCenteredText(
            $image,
            $fontPath,
            $checkIn . ' - ' . $checkOut,
            (int) round($width * 0.014),
            (int) round($height * 0.855),
            $mutedText
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'guest-ticket-');
        if ($tempFile === false) {
            imagedestroy($image);
            throw new RuntimeException('Unable to allocate temporary storage for ticket generation.');
        }

        $pngPath = $tempFile . '.png';
        if (!imagepng($image, $pngPath)) {
            @unlink($tempFile);
            throw new RuntimeException('Failed to export the invitation ticket image.');
        }

        @unlink($tempFile);

        return $pngPath;
    }

    private function buildQrImage(array $guest): ?\GdImage
    {
        $qrPayload = $this->requiredValue($guest, 'registrationNumber');

        $writer = new PngWriter();
        $result = $writer->write(new QrCode(
            data: $qrPayload,
            size: 340,
            margin: 12
        ));

        $image = imagecreatefromstring($result->getString());

        return $image === false ? null : $image;
    }

    private function drawCenteredText(\GdImage $image, ?string $fontPath, string $text, int $fontSize, int $baselineY, int $color): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($fontPath !== null && function_exists('imagettfbbox') && function_exists('imagettftext')) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);

            if (is_array($box)) {
                $textWidth = (int) abs($box[2] - $box[0]);
                $x = (int) round((imagesx($image) - $textWidth) / 2);
                imagettftext($image, $fontSize, 0, $x, $baselineY, $color, $fontPath, $text);
                return;
            }
        }

        $font = 5;
        $x = (int) round((imagesx($image) - (imagefontwidth($font) * strlen($text))) / 2);
        imagestring($image, $font, max(0, $x), max(0, $baselineY - imagefontheight($font)), $text, $color);
    }

    private function resolveFontPath(): ?string
    {
        $candidates = [
            dirname(__DIR__) . '/templates/fonts/IBMPlexSerif-Regular.ttf',
            'C:/Windows/Fonts/georgia.ttf',
            'C:/Windows/Fonts/times.ttf',
            'C:/Windows/Fonts/arial.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function requiredValue(array $guest, string $key): string
    {
        $value = trim((string) ($guest[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException("Missing guest field: {$key}.");
        }

        return $value;
    }

    private function formatDate(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp === false ? $value : date('d M Y', $timestamp);
    }

    private function pixelsToMillimeters(int $width, int $height): array
    {
        $scale = 25.4 / 96;
        return [
            round($width * $scale, 2),
            round($height * $scale, 2),
        ];
    }

    private function imageDimensions(string $path): array
    {
        $size = getimagesize($path);
        if ($size === false) {
            throw new RuntimeException('Unable to read the generated invitation image.');
        }

        return [(int) $size[0], (int) $size[1]];
    }

    private function buildFilename(array $guest): string
    {
        $slug = strtolower($this->requiredValue($guest, 'fullName'));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? 'guest';
        $slug = trim($slug, '-');

        return sprintf('invitation-ticket-%s.pdf', $slug !== '' ? $slug : 'guest');
    }
}
