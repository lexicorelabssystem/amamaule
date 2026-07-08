<?php

namespace App\Services;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetExportService
{
    public function download(array $headings, iterable $rows, string $filename, string $format): StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), Response::HTTP_UNPROCESSABLE_ENTITY);

        return response()->streamDownload(function () use ($headings, $rows, $format) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray([$headings]);
            $line = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([array_map([$this, 'safeCell'], array_values($row))], null, 'A'.$line++);
            }
            $writer = $format === 'xlsx' ? new Xlsx($spreadsheet) : new Csv($spreadsheet);
            if ($writer instanceof Csv) {
                $writer->setDelimiter(';')->setUseBOM(true);
            }
            $writer->save('php://output');
        }, $filename.'.'.$format, ['Content-Type' => $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv; charset=UTF-8']);
    }

    public function safeCell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? chr(39).$value : $value;
    }
}
