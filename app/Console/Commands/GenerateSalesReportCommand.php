<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

class GenerateSalesReportCommand extends Command
{
    protected $signature = 'pos:sales-report
                            {--start-date= : Start date (YYYY-MM-DD)}
                            {--end-date= : End date (YYYY-MM-DD)}
                            {--warehouse= : Warehouse ID}
                            {--format=json : Output format (json|csv)}';

    protected $description = 'Generate sales report';

    public function handle(ReportService $reportService): int
    {
        $filters = array_filter([
            'start_date' => $this->option('start-date'),
            'end_date' => $this->option('end-date'),
            'warehouse_id' => $this->option('warehouse'),
        ]);

        $report = $reportService->salesReport($filters);

        if ($this->option('format') === 'csv') {
            $this->exportToCsv($report);
        } else {
            $this->info('Sales Report');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Sales', $report['total_sales']],
                    // ['Total Amount', '. number_format($report['total_amount'], 2) ],
                    // ['Average Sale', '. number_format($report['average_sale'], 2)]
                ]
            );
        }

        return 0;
    }

    private function exportToCsv(array $report): void
    {
        $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.csv';
        $path = storage_path("app/reports/{$filename}");

        $handle = fopen($path, 'w');
        fputcsv($handle, ['Sale Number', 'Date', 'Customer', 'Total Amount', 'Status']);

        foreach ($report['sales'] as $sale) {
            fputcsv($handle, [
                $sale->sale_number,
                $sale->sale_date->format('Y-m-d H:i:s'),
                $sale->customer->name ?? 'Walk-in',
                $sale->total_amount,
                $sale->status
            ]);
        }

        fclose($handle);
        $this->info("Report exported to: {$path}");
    }
}
