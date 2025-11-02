<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => (string) $this->id,
            'invoiceNumber' => $this->invoice_number,
            'date'          => optional($this->date)->format('Y-m-d'),
            'customerName'  => $this->customer_name,
            'items'         => InvoiceItemResource::collection($this->whenLoaded('items')),
            'discount'      => $this->discount_type ? [
                'type'  => $this->discount_type,
                'value' => (float) $this->discount_value,
            ] : null,
            'subtotal'       => (float) $this->subtotal,
            'discountAmount' => (float) $this->discount_amount,
            'amount'         => (float) $this->amount,
            'vat'            => (float) $this->vat,
            'totalAmount'    => (float) $this->total_amount,
            'status'         => $this->status,
            'createdAt'      => optional($this->created_at)->toIso8601String(),
            'updatedAt'      => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
