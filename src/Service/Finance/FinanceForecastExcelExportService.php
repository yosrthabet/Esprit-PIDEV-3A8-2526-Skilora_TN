<?php

namespace App\Service\Finance;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Export XLSX de la prévision masse salariale (phpoffice/phpspreadsheet).
 */
final class FinanceForecastExcelExportService
{
    /**
     * @param array{
     *   historical: list<array{period: string, total_net: float, ...}>,
     *   forecast: list<array{period: string, total_net: float, band_low: float, band_high: float}>,
     *   meta: array<string, mixed>
     * } $forecastPayload
     */
    public function buildSpreadsheet(array $forecastPayload): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prévision masse');

        $sheet->setCellValue('A1', 'Skilora — Prévision masse salariale (net estimé)');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $meta = $forecastPayload['meta'] ?? [];
        $row = 3;
        $sheet->setCellValue('A'.$row, 'Mois historique utilisés');
        $sheet->setCellValue('B'.$row, (string) ($meta['history_months_used'] ?? ''));
        ++$row;
        $sheet->setCellValue('A'.$row, 'Mois prévus');
        $sheet->setCellValue('B'.$row, (string) ($meta['forecast_months'] ?? ''));
        ++$row;
        $sheet->setCellValue('A'.$row, 'Scénario %');
        $sheet->setCellValue('B'.$row, (string) ($meta['scenario_percent'] ?? ''));
        ++$row;
        $sheet->setCellValue('A'.$row, '+ Net TND / mois');
        $sheet->setCellValue('B'.$row, (string) ($meta['scenario_extra_net_monthly'] ?? ''));

        $row += 2;
        $sheet->setCellValue('A'.$row, 'Historique');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        ++$row;
        $sheet->fromArray(['Période', 'Net estimé (TND)', 'Brut (TND)', 'Nb bulletins'], null, 'A'.$row);
        ++$row;
        foreach ($forecastPayload['historical'] ?? [] as $h) {
            $sheet->fromArray([
                $h['period'] ?? '',
                (float) ($h['total_net'] ?? 0),
                (float) ($h['total_gross'] ?? 0),
                (int) ($h['payslip_count'] ?? 0),
            ], null, 'A'.$row);
            ++$row;
        }

        $row += 1;
        $sheet->setCellValue('A'.$row, 'Prévision');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        ++$row;
        $sheet->fromArray(['Période', 'Net (TND)', 'Basse', 'Haute'], null, 'A'.$row);
        ++$row;
        foreach ($forecastPayload['forecast'] ?? [] as $f) {
            $sheet->fromArray([
                $f['period'] ?? '',
                (float) ($f['total_net'] ?? 0),
                (float) ($f['band_low'] ?? 0),
                (float) ($f['band_high'] ?? 0),
            ], null, 'A'.$row);
            ++$row;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
