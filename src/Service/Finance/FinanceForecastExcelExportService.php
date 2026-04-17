<?php

namespace App\Service\Finance;

/**
 * Fallback d'export tabulaire pour la prévision masse salariale.
 * Le nom du service source est conservé, mais l'export est généré en CSV
 * tant que PhpSpreadsheet n'est pas disponible sur la plateforme locale.
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
    public function buildCsv(array $forecastPayload): string
    {
        $lines = [];
        $lines[] = $this->csvRow(['Skilora - Prevision masse salariale (net estime)']);

        $meta = $forecastPayload['meta'] ?? [];
        $lines[] = $this->csvRow([]);
        $lines[] = $this->csvRow(['Mois historique utilises', (string) ($meta['history_months_used'] ?? '')]);
        $lines[] = $this->csvRow(['Mois prevus', (string) ($meta['forecast_months'] ?? '')]);
        $lines[] = $this->csvRow(['Scenario %', (string) ($meta['scenario_percent'] ?? '')]);
        $lines[] = $this->csvRow(['+ Net TND / mois', (string) ($meta['scenario_extra_net_monthly'] ?? '')]);

        $lines[] = $this->csvRow([]);
        $lines[] = $this->csvRow(['Historique']);
        $lines[] = $this->csvRow(['Periode', 'Net estime (TND)', 'Brut (TND)', 'Nb bulletins']);
        foreach ($forecastPayload['historical'] ?? [] as $h) {
            $lines[] = $this->csvRow([
                $h['period'] ?? '',
                (string) ($h['total_net'] ?? 0),
                (string) ($h['total_gross'] ?? 0),
                (string) ($h['payslip_count'] ?? 0),
            ]);
        }

        $lines[] = $this->csvRow([]);
        $lines[] = $this->csvRow(['Prevision']);
        $lines[] = $this->csvRow(['Periode', 'Net (TND)', 'Basse', 'Haute']);
        foreach ($forecastPayload['forecast'] ?? [] as $f) {
            $lines[] = $this->csvRow([
                $f['period'] ?? '',
                (string) ($f['total_net'] ?? 0),
                (string) ($f['band_low'] ?? 0),
                (string) ($f['band_high'] ?? 0),
            ]);
        }

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * @param list<string> $cells
     */
    private function csvRow(array $cells): string
    {
        $escaped = array_map(static function (string $cell): string {
            $cell = str_replace('"', '""', $cell);

            return '"'.$cell.'"';
        }, $cells);

        return implode(';', $escaped);
    }
}
