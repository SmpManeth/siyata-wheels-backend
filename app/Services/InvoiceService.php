<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function nextInvoiceNumber(): string
    {
        $prefix = config('invoice.number_prefix', 'INV-');
        $pad    = (int) config('invoice.number_pad', 3);

        // Get max numeric part
        $last = Invoice::withTrashed()
            ->where('invoice_number', 'LIKE', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = 1;
        if ($last && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * Validate & compute totals based on items + discount.
     * All money math is done using integer cents to avoid FP issues.
     */
    public function computeTotals(array $items, ?array $discount): array
    {
        // Sum subtotal in cents = SUM(rs*100 + cts)
        $subtotalCents = 0;

        foreach ($items as $row) {
            $rs  = (int) ($row['rs'] ?? 0);
            $cts = (int) ($row['cts'] ?? 0);
            if ($cts < 0 || $cts > 99) {
                throw new \InvalidArgumentException('Item cents (cts) must be between 0 and 99.');
            }
            $subtotalCents += ($rs * 100) + $cts;
        }

        $discountCents = 0;
        if ($discount && isset($discount['type'], $discount['value'])) {
            if ($discount['type'] === 'percentage') {
                // round to nearest cent
                $discountCents = (int) round($subtotalCents * ((float) $discount['value']) / 100);
            } else {
                // fixed value in rupees; convert to cents
                $discountCents = (int) round(((float) $discount['value']) * 100);
            }
            $discountCents = max(0, min($discountCents, $subtotalCents));
        }

        $totalCents = $subtotalCents - $discountCents;

        // According to spec:
        // amount (without VAT) = total * 0.82
        // vat = total * 0.18
        $vatRate  = (float) config('invoice.vat_rate', 0.18);
        $amountRatio = (float) config('invoice.amount_without_vat_ratio', 0.82);

        $amountCents = (int) round($totalCents * $amountRatio);
        $vatCents    = $totalCents - $amountCents; // ensures sum integrity

        return [
            'subtotal'       => $this->centsToDecimal($subtotalCents),
            'discount_amount'=> $this->centsToDecimal($discountCents),
            'total_amount'   => $this->centsToDecimal($totalCents),
            'amount'         => $this->centsToDecimal($amountCents),
            'vat'            => $this->centsToDecimal($vatCents),
        ];
    }

    public function create(int $userId, array $payload): Invoice
    {
        return DB::transaction(function () use ($userId, $payload) {
            $items = $payload['items'] ?? [];
            $discountArr = $payload['discount'] ?? null;

            $totals = $this->computeTotals($items, $discountArr);

            $invoiceNumber = $payload['invoiceNumber'] ?? $this->nextInvoiceNumber();

            $invoice = Invoice::create([
                'user_id'        => $userId,
                'invoice_number' => $invoiceNumber,
                'date'           => $payload['date'],
                'customer_name'  => $payload['customerName'],

                'discount_type'  => $discountArr['type'] ?? null,
                'discount_value' => $discountArr['value'] ?? null,

                'subtotal'       => $totals['subtotal'],
                'discount_amount'=> $totals['discount_amount'],
                'amount'         => $totals['amount'],
                'vat'            => $totals['vat'],
                'total_amount'   => $totals['total_amount'],

                'status'         => $payload['status'] ?? 'draft',
            ]);

            // Items
            foreach ($items as $row) {
                $invoice->items()->create(Arr::only($row, [
                    'qty', 'description', 'rate', 'rs', 'cts'
                ]));
            }

            return $invoice->load('items');
        });
    }

    public function update(Invoice $invoice, array $payload): Invoice
    {
        return DB::transaction(function () use ($invoice, $payload) {
            $items = $payload['items'] ?? [];
            $discountArr = $payload['discount'] ?? null;

            $totals = $this->computeTotals($items, $discountArr);

            $invoice->fill([
                'invoice_number' => $payload['invoiceNumber'] ?? $invoice->invoice_number,
                'date'           => $payload['date'],
                'customer_name'  => $payload['customerName'],

                'discount_type'  => $discountArr['type'] ?? null,
                'discount_value' => $discountArr['value'] ?? null,

                'subtotal'       => $totals['subtotal'],
                'discount_amount'=> $totals['discount_amount'],
                'amount'         => $totals['amount'],
                'vat'            => $totals['vat'],
                'total_amount'   => $totals['total_amount'],

                'status'         => $payload['status'] ?? $invoice->status,
            ])->save();

            // Replace items (simple approach; can optimize to diff)
            $invoice->items()->delete();
            foreach ($items as $row) {
                $invoice->items()->create(Arr::only($row, [
                    'qty', 'description', 'rate', 'rs', 'cts'
                ]));
            }

            return $invoice->load('items');
        });
    }

    private function centsToDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $rupees = intdiv($cents, 100);
        $cts    = $cents % 100;
        return $sign . sprintf('%d.%02d', $rupees, $cts);
    }
}
