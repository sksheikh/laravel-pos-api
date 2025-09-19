<?php
namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptService
{
    public function generate(Sale $sale): string
    {
        $pdf = Pdf::loadView('receipts.sale', compact('sale'));

        $filename = "receipt_{$sale->sale_number}.pdf";
        $path = storage_path("app/receipts/{$filename}");

        $pdf->save($path);

        return $path;
    }
}
