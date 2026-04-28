<?php

namespace App\Services;

use App\Core\ApiException;

class GuestImportService
{
    private const REQUIRED_COLUMNS = ['full_name', 'company', 'position', 'seat_number'];

    public function parseUploadedFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ApiException('Please upload a valid Excel file.', 422);
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $tempPath = (string) ($file['tmp_name'] ?? '');

        if ($tempPath === '' || !is_file($tempPath)) {
            throw new ApiException('Uploaded file could not be read.', 422);
        }

        return match ($extension) {
            'xlsx' => $this->parseXlsx($tempPath),
            'csv' => $this->parseCsv($tempPath),
            default => throw new ApiException('Only .xlsx or .csv files are supported.', 422),
        };
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new ApiException('Unable to open the uploaded CSV file.', 422);
        }

        $rows = [];
        $headers = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);
                $this->assertRequiredColumns($headers);
                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $this->mapImportRow($headers, $row, count($rows) + 2);
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new ApiException('PHP ZipArchive extension is required for Excel import.', 500);
        }

        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new ApiException('Unable to open the uploaded Excel file.', 422);
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($worksheetXml === false) {
                throw new ApiException('The Excel file must contain data in the first worksheet.', 422);
            }

            return $this->parseWorksheetXml($worksheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    private function readSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = simplexml_load_string($xml);

        if ($document === false) {
            throw new ApiException('Unable to read shared strings from the Excel file.', 422);
        }

        $strings = [];

        foreach ($document->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $value = '';
            foreach ($item->r as $run) {
                $value .= (string) $run->t;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function parseWorksheetXml(string $xml, array $sharedStrings): array
    {
        $document = simplexml_load_string($xml);

        if ($document === false) {
            throw new ApiException('Unable to read rows from the Excel file.', 422);
        }

        $document->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sheetRows = $document->xpath('//main:sheetData/main:row') ?: [];

        if ($sheetRows === []) {
            return [];
        }

        $headers = null;
        $rows = [];

        foreach ($sheetRows as $index => $row) {
            $values = $this->extractRowValues($row, $sharedStrings);

            if ($headers === null) {
                $headers = $this->normalizeHeaders($values);
                $this->assertRequiredColumns($headers);
                continue;
            }

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $rows[] = $this->mapImportRow($headers, $values, $index + 1);
        }

        return $rows;
    }

    private function extractRowValues(\SimpleXMLElement $row, array $sharedStrings): array
    {
        $values = [];

        foreach ($row->c as $cell) {
            $reference = (string) $cell['r'];
            $column = $this->columnIndexFromCellReference($reference);
            $type = (string) $cell['t'];
            $rawValue = isset($cell->v) ? (string) $cell->v : '';

            if ($type === 's') {
                $value = $sharedStrings[(int) $rawValue] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string) ($cell->is->t ?? '');
            } else {
                $value = $rawValue;
            }

            $values[$column] = trim($value);
        }

        if ($values === []) {
            return [];
        }

        ksort($values);

        $normalized = [];
        $maxColumn = (int) max(array_keys($values));

        for ($column = 0; $column <= $maxColumn; $column++) {
            $normalized[] = $values[$column] ?? '';
        }

        return $normalized;
    }

    private function columnIndexFromCellReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($reference));
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(
            fn ($header) => strtolower(trim((string) $header)),
            $headers
        );
    }

    private function assertRequiredColumns(array $headers): void
    {
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        if ($missing !== []) {
            throw new ApiException(
                'Missing required columns: ' . implode(', ', $missing) . '.',
                422
            );
        }
    }

    private function mapImportRow(array $headers, array $values, int $rowNumber): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = trim((string) ($values[$index] ?? ''));
        }

        if (($row['full_name'] ?? '') === '') {
            throw new ApiException("Row {$rowNumber} is missing full_name.", 422);
        }

        $seatNumber = $row['seat_number'] ?? '';
        $slug = $this->slugify($row['full_name']);
        $seatSlug = $seatNumber !== '' ? $this->slugify($seatNumber) : 'row-' . $rowNumber;

        return [
            'fullName' => $row['full_name'],
            'company' => $row['company'] ?? '',
            'position' => $row['position'] ?? '',
            'seatNumber' => $seatNumber,
            'email' => sprintf('%s-%s@import.local', $slug !== '' ? $slug : 'guest', $seatSlug),
            'phone' => '',
            'suite' => $seatNumber !== '' ? 'Seat ' . $seatNumber : 'General Seating',
            'checkIn' => date('Y-m-d'),
            'checkOut' => date('Y-m-d'),
            'specialRequests' => '',
            'vipStatus' => false,
            'status' => 'active',
        ];
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
