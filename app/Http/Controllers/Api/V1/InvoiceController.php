<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service
    ) {}

    // GET /invoices
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $limit = (int) $request->query('limit', 50);
        $sort  = $request->query('sort', 'date'); // date | invoiceNumber | totalAmount
        $order = $request->query('order', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'date'          => 'date',
            'invoiceNumber' => 'invoice_number',
            'totalAmount'   => 'total_amount',
        ];
        $sortCol = $sortMap[$sort] ?? 'date';

        $paginator = Invoice::with(['items'])
            ->forUser($user->id)
            ->orderBy($sortCol, $order)
            ->paginate($limit)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => [
                'invoices' => InvoiceResource::collection($paginator->items()),
                'pagination' => [
                    'currentPage'  => $paginator->currentPage(),
                    'totalPages'   => $paginator->lastPage(),
                    'totalItems'   => $paginator->total(),
                    'itemsPerPage' => $paginator->perPage(),
                ],
            ],
        ]);
    }

    // GET /invoices/{id}
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $invoice = Invoice::with('items')
            ->forUser($user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => (new InvoiceResource($invoice)),
        ]);
    }

    // POST /invoices
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $user = $request->user();
        $invoice = $this->service->create($user->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data'    => (new InvoiceResource($invoice)),
        ]);
    }

    // PUT /invoices/{id}
    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $invoice = Invoice::forUser($user->id)->findOrFail($id);
        $invoice = $this->service->update($invoice, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data'    => (new InvoiceResource($invoice)),
        ]);
    }

    // DELETE /invoices/{id}
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $invoice = Invoice::forUser($user->id)->findOrFail($id);
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    // GET /invoices/generate-number
    public function generateNumber(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'invoiceNumber' => $this->service->nextInvoiceNumber(),
            ],
        ]);
    }

    // GET /invoices/{id}/pdf (stub)
    public function pdf(Request $request, int $id)
    {
        // Stub: Return 501 Not Implemented
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'PDF generation not implemented yet',
            ],
        ], 501);
    }

    // POST /invoices/{id}/email (stub)
    public function email(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'recipientEmail' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $invoice = Invoice::forUser($user->id)->findOrFail($id);

        // Stub for mail send (implement your Mailable later)
        // Mail::to($request->recipientEmail)->send(new \App\Mail\InvoiceMail($invoice, $request->message));

        return response()->json([
            'success' => true,
            'message' => 'Invoice emailed successfully (stub)',
        ]);
    }
}
