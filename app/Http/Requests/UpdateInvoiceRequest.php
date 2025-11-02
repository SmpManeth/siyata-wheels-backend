<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoiceNumber' => ['nullable', 'string', 'max:50'],
            'date'          => ['required', 'date'],
            'customerName'  => ['required', 'string', 'max:255'],

            'items'         => ['required', 'array', 'min:1'],
            'items.*.qty'   => ['required', 'integer', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.rate'  => ['nullable', 'numeric', 'min:0'],
            'items.*.rs'    => ['required', 'integer', 'min:0'],
            'items.*.cts'   => ['required', 'integer', 'min:0', 'max:99'],

            'discount'              => ['nullable', 'array'],
            'discount.type'         => ['required_with:discount', 'in:fixed,percentage'],
            'discount.value'        => ['required_with:discount', 'numeric', 'min:0'],

            'status'       => ['nullable', 'in:draft,sent,paid,overdue'],
        ];
    }
}
