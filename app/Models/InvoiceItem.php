<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'qty',
        'description',
        'rate',
        'rs',
        'cts',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'rs' => 'integer',
        'cts' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function amountAsDecimal(): string
    {
        // Convert rs/cts into a decimal string (e.g., "123.45")
        $rupees = (int) $this->rs;
        $cents  = (int) $this->cts;
        $centsPadded = str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
        return "{$rupees}.{$centsPadded}";
    }
}
