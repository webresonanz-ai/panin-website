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
        $yPositionName = (int) round($height * 0.42);
        $yGap = 60;
        $fontPath = $this->resolveFontPath();

        $goldenText = imagecolorallocate($image, 246, 214, 95);
        $goldenHighlight = imagecolorallocate($image, 222, 163, 52);
        $whiteGlitter = imagecolorallocatealpha($image, 255, 255, 255, 30);
        $deepShadow = imagecolorallocatealpha($image, 36, 22, 10, 70);

        $fullName = $this->requiredValue($guest, 'fullName');
        $gaSoPosition = trim((string) ($guest['gaSoPosition'] ?? ''));
        $seatNumber = trim((string) ($guest['seatNumber'] ?? ''));

        $boldFontPath = dirname(__DIR__) . '/templates/fonts/Cinzel/static/Cinzel-Bold.ttf';
        $this->drawCenteredText3D(
            $image,
            $boldFontPath,
            $fullName,
            (int) round($width * 0.04),
            $yPositionName,
            $goldenText,
            $goldenHighlight,
            $whiteGlitter,
            $deepShadow
        );

        if ($gaSoPosition !== '') {
            $yPositionName += $yGap;
            $this->drawCenteredText3D(
                $image,
                $boldFontPath,
                $gaSoPosition,
                (int) round($width * 0.025),
                $yPositionName,
                $goldenText,
                $goldenHighlight,
                $whiteGlitter,
                $deepShadow
            );
        }

        $qrImage = $this->buildQrImage($guest);
        if ($qrImage !== null) {
            $yPositionName += $yGap;

            $qrSize = (int) round(min($width, $height) * 0.35);
            $qrX = (int) round(($width - $qrSize) / 2);
            $qrY = $yPositionName;
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

        $yPositionName += $yGap + $qrSize;
        $this->drawCenteredText3D(
            $image,
            $boldFontPath,
            'Seat ' . ($seatNumber !== '' ? $seatNumber : 'Unassigned'),
            (int) round($width * 0.035),
            $yPositionName,
            $goldenText,
            $goldenHighlight,
            $whiteGlitter,
            $deepShadow
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'guest-ticket-');
        if ($tempFile === false) {
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

    private function drawCenteredText3D(
        \GdImage $image,
        ?string $fontPath,
        string $text,
        int $fontSize,
        int $baselineY,
        int $faceColor,
        int $highlightColor,
        int $glitterColor,
        int $shadowColor
    ): void {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($fontPath !== null && function_exists('imagettfbbox') && function_exists('imagettftext')) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $text);

            if (is_array($box)) {
                $textWidth = (int) abs($box[2] - $box[0]);
                $x = (int) round((imagesx($image) - $textWidth) / 2);

                $this->drawTextBackdrop($image, $fontPath, $text, $fontSize, $x, $baselineY);

                foreach ([[4, 4], [3, 3], [2, 2]] as [$offsetX, $offsetY]) {
                    imagettftext($image, $fontSize, 0, $x + $offsetX, $baselineY + $offsetY, $shadowColor, $fontPath, $text);
                }

                imagettftext($image, $fontSize, 0, $x + 1, $baselineY - 1, $highlightColor, $fontPath, $text);
                imagettftext($image, $fontSize, 0, $x, $baselineY, $faceColor, $fontPath, $text);
                $this->addTextGlitter($image, $fontPath, $text, $fontSize, $x, $baselineY, $glitterColor);
                return;
            }
        }

        $this->drawCenteredText($image, $fontPath, $text, $fontSize, $baselineY, $faceColor);
    }

    private function drawTextBackdrop(
        \GdImage $image,
        string $fontPath,
        string $text,
        int $fontSize,
        int $x,
        int $baselineY
    ): void {
        $fadeLayers = [
            ['radius' => 7, 'alpha' => 122],
            ['radius' => 5, 'alpha' => 124],
            ['radius' => 3, 'alpha' => 126],
        ];

        foreach ($fadeLayers as $layer) {
            $backdropColor = imagecolorallocatealpha($image, 0, 0, 0, $layer['alpha']);

            for ($offsetY = -$layer['radius']; $offsetY <= $layer['radius']; $offsetY++) {
                for ($offsetX = -$layer['radius']; $offsetX <= $layer['radius']; $offsetX++) {
                    if (($offsetX * $offsetX) + ($offsetY * $offsetY) > $layer['radius'] * $layer['radius']) {
                        continue;
                    }

                    imagettftext(
                        $image,
                        $fontSize,
                        0,
                        $x + $offsetX,
                        $baselineY + $offsetY,
                        $backdropColor,
                        $fontPath,
                        $text
                    );
                }
            }
        }
    }

    private function addTextGlitter(
        \GdImage $image,
        string $fontPath,
        string $text,
        int $fontSize,
        int $x,
        int $baselineY,
        int $glitterColor
    ): void {
        $maskBox = imagettfbbox($fontSize, 0, $fontPath, $text);
        if (!is_array($maskBox)) {
            return;
        }

        $minX = min($maskBox[0], $maskBox[6]);
        $maxX = max($maskBox[2], $maskBox[4]);
        $minY = min($maskBox[5], $maskBox[7]);
        $maxY = max($maskBox[1], $maskBox[3]);

        $maskWidth = max(1, $maxX - $minX + 8);
        $maskHeight = max(1, $maxY - $minY + 8);
        $mask = imagecreatetruecolor($maskWidth, $maskHeight);
        if ($mask === false) {
            return;
        }

        $transparent = imagecolorallocate($mask, 0, 0, 0);
        imagefill($mask, 0, 0, $transparent);
        $white = imagecolorallocate($mask, 255, 255, 255);
        imagettftext($mask, $fontSize, 0, 4 - $minX, $baselineY - ($baselineY + $minY) + 4, $white, $fontPath, $text);

        $sparkleCount = max(12, (int) floor(strlen($text) * 3.5));
        $placed = 0;
        $attempts = 0;

        while ($placed < $sparkleCount && $attempts < $sparkleCount * 12) {
            $attempts++;
            $sparkleX = random_int(0, $maskWidth - 1);
            $sparkleY = random_int(0, $maskHeight - 1);

            if (imagecolorat($mask, $sparkleX, $sparkleY) !== $white) {
                continue;
            }

            $targetX = $x + $sparkleX + $minX - 4;
            $targetY = $baselineY + $sparkleY + $minY - 4;
            $this->drawSparkle($image, $targetX, $targetY, $glitterColor);
            $placed++;
        }

    }

    private function drawSparkle(\GdImage $image, int $centerX, int $centerY, int $color): void
    {
        imagesetpixel($image, $centerX, $centerY, $color);

        foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$offsetX, $offsetY]) {
            imagesetpixel($image, $centerX + $offsetX, $centerY + $offsetY, $color);
        }
    }

    private function resolveFontPath(): ?string
    {
        $candidates = [
            dirname(__DIR__) . '/templates/fonts/Cinzel/static/Cinzel-Regular.ttf',
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
