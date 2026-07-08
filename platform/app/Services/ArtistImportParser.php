<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArtistImportParser
{
    public const REQUIRED_HEADERS = [
        'legal_name',
        'email_contact',
    ];

    public const OPTIONAL_HEADERS = [
        'public_name',
        'artistic_name',
        'document_number',
        'phone',
        'website',
        'region',
        'province',
        'commune',
        'address',
        'main_discipline',
        'bio_short',
        'bio_long',
    ];

    public const ALLOWED_EXTENSIONS = ['csv', 'xlsx', 'xls'];

    public function parse(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return $this->errorResult('Formato no soportado. Use: '.implode(', ', self::ALLOWED_EXTENSIONS));
        }

        try {
            $spreadsheet = $this->loadSpreadsheet($path, $extension);
            $worksheet = $spreadsheet->getActiveSheet();
        } catch (\Throwable $e) {
            return $this->errorResult('No se pudo leer el archivo: '.$e->getMessage());
        }

        $headers = $this->extractHeaders($worksheet);

        if (empty($headers)) {
            return $this->errorResult('El archivo no contiene encabezados en la primera fila.');
        }

        $missing = array_diff(self::REQUIRED_HEADERS, $headers);

        if (! empty($missing)) {
            return $this->errorResult(
                'Faltan columnas obligatorias: '.implode(', ', $missing),
                ['headers' => $headers]
            );
        }

        $rows = $this->extractRows($worksheet, $headers);

        return [
            'valid' => true,
            'headers' => $headers,
            'rows' => $rows,
            'errors' => [],
        ];
    }

    protected function loadSpreadsheet(string $path, string $extension): Spreadsheet
    {
        if ($extension === 'csv') {
            $reader = new CsvReader;
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter($this->detectCsvDelimiter($path));
            $reader->setEnclosure('"');

            return $reader->load($path);
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        return $reader->load($path);
    }

    protected function detectCsvDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ',';
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false) {
            return ',';
        }

        $semicolons = substr_count($firstLine, ';');
        $commas = substr_count($firstLine, ',');

        return $semicolons >= $commas ? ';' : ',';
    }

    protected function extractHeaders(Worksheet $worksheet): array
    {
        $headers = [];
        $iterator = $worksheet->getRowIterator(1, 1);

        foreach ($iterator as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                $headers[] = $this->normalizeHeader($value);
            }
        }

        return array_values(array_filter($headers, fn ($h) => $h !== ''));
    }

    protected function normalizeHeader(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $header = strtolower(trim((string) $value));
        $header = str_replace([' ', '-', '/', '\\'], '_', $header);

        return preg_replace('/_+/', '_', $header) ?? '';
    }

    protected function extractRows(Worksheet $worksheet, array $headers): array
    {
        $rows = [];
        $highestRow = $worksheet->getHighestRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $data = [];
            $colIndex = 0;

            foreach ($worksheet->getRowIterator($rowNumber, $rowNumber) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $header = $headers[$colIndex] ?? null;

                    if ($header !== null) {
                        $data[$header] = $this->normalizeValue($cell->getValue());
                    }

                    $colIndex++;
                }
            }

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'data' => $data,
            ];
        }

        return $rows;
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    protected function errorResult(string $message, array $extra = []): array
    {
        return array_merge([
            'valid' => false,
            'headers' => [],
            'rows' => [],
            'errors' => [$message],
        ], $extra);
    }
}
