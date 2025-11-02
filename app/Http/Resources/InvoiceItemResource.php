<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'qty'         => (int) $this->qty,
            'description' => $this->description,
            'rate'        => (float) $this->rate,
            'rs'          => (int) $this->rs,
            'cts'         => (int) $this->cts,
        ];
    }
}
