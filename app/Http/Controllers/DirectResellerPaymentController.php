<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DirectResellerPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = ResellerPayment::with('reseller')
            ->whereHas('reseller', fn ($q) => $q->direct())
            ->latest('payment_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('reseller', function ($resellerQuery) use ($search) {
                    $resellerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%");
                })->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('method') && $request->input('method') != '') {
            $query->where('payment_method', $request->input('method'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->input('end_date'));
        }

        $payments = $query->paginate(10);
        $payments->appends($request->all()); // Preserve filters in pagination links

        return view('direct-resellers.payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $resellers = Reseller::direct()->orderBy('name')->get();

        return view('direct-resellers.payments.create', compact('resellers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reseller_id' => [
                'required',
                Rule::exists('resellers', 'id')->where('reseller_type', Reseller::TYPE_DIRECT_RESELLER),
            ],
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:bank,cash,other',
            'reference_id' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            // Create Payment
            ResellerPayment::create([
                'reseller_id' => $validated['reseller_id'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_id' => $validated['reference_id'] ?? null,
                'payment_date' => $validated['payment_date'],
                'status' => 'paid',
            ]);

            // Deduct amount from Reseller Due
            $reseller = Reseller::direct()->findOrFail($validated['reseller_id']);
            $reseller->due_amount -= $validated['amount'];
            $reseller->save();
        });

        return redirect()->route('direct-reseller-payments.index')->with('success', 'Payment recorded successfully and due amount updated.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResellerPayment $resellerPayment)
    {
        $this->ensureRegularPayment($resellerPayment);

        $resellers = Reseller::direct()->orderBy('name')->get();

        return view('direct-resellers.payments.edit', compact('resellerPayment', 'resellers'));
    }

    /**
     * Download a single invoice.
     */
    public function downloadInvoice(ResellerPayment $resellerPayment)
    {
        $this->ensureRegularPayment($resellerPayment);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('direct-resellers.payments.invoice', ['payment' => $resellerPayment]);

        return $pdf->download('receipt-'.str_pad($resellerPayment->id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    /**
     * Download filtered or selected invoices as a ZIP file.
     */
    public function downloadBulkInvoices(Request $request)
    {
        $zip = new \ZipArchive;
        $fileName = 'payment_vouchers_'.date('Y-m-d_His').'.zip';

        // Ensure the directory exists
        if (! file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }
        $zipPath = storage_path('app/public/temp/'.$fileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create Zip file.');
        }

        // Logic:
        // 1. If 'payment_ids' array is present (from checkboxes), use that.
        // 2. Else use active filters.
        $paymentIds = $request->input('payment_ids');

        if ($paymentIds) {
            // Explode if it's a comma-separated string (sometimes happens with hidden inputs),
            // but usually array if from checkboxes.
            if (is_string($paymentIds)) {
                $paymentIds = explode(',', $paymentIds);
            }

            $payments = ResellerPayment::with('reseller')
                ->whereHas('reseller', fn ($q) => $q->direct())
                ->whereIn('id', $paymentIds)
                ->get();
        } else {
            // Fallback to Filters
            $search = $request->input('search');
            $query = ResellerPayment::with('reseller')
                ->whereHas('reseller', fn ($q) => $q->direct())
                ->latest('payment_date');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('reseller', function ($resellerQuery) use ($search) {
                        $resellerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%");
                    })->orWhere('reference_id', 'like', "%{$search}%");
                });
            }

            if ($request->has('method') && $request->input('method') != '') {
                $query->where('payment_method', $request->input('method'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('payment_date', '>=', $request->input('start_date'));
            }

            if ($request->filled('end_date')) {
                $query->whereDate('payment_date', '<=', $request->input('end_date'));
            }

            $payments = $query->get();
        }

        if ($payments->isEmpty()) {
            return back()->with('error', 'No payments selected to download.');
        }

        foreach ($payments as $payment) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('direct-resellers.payments.invoice', ['payment' => $payment]);
            $content = $pdf->output();
            $zip->addFromString('voucher-'.str_pad($payment->id, 6, '0', STR_PAD_LEFT).'.pdf', $content);
        }
        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ResellerPayment $resellerPayment)
    {
        $this->ensureRegularPayment($resellerPayment);

        if ($resellerPayment->status === 'cancelled') {
            return redirect()->route('direct-reseller-payments.index')->with('error', 'Cancelled payments cannot be edited.');
        }

        $validated = $request->validate([
            'reseller_id' => [
                'required',
                Rule::exists('resellers', 'id')->where('reseller_type', Reseller::TYPE_DIRECT_RESELLER),
            ],
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:bank,cash,other',
            'reference_id' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $resellerPayment) {
            $oldResellerId = (int) $resellerPayment->reseller_id;
            $newResellerId = (int) $validated['reseller_id'];
            $oldAmount = (float) $resellerPayment->amount;
            $newAmount = (float) $validated['amount'];

            // Update Payment
            $resellerPayment->update([
                'reseller_id' => $newResellerId,
                'amount' => $newAmount,
                'payment_method' => $validated['payment_method'],
                'reference_id' => $validated['reference_id'] ?? null,
                'payment_date' => $validated['payment_date'],
            ]);

            if ($oldResellerId === $newResellerId) {
                $reseller = Reseller::direct()->lockForUpdate()->findOrFail($newResellerId);
                $reseller->due_amount -= ($newAmount - $oldAmount);
                $reseller->save();

                return;
            }

            $oldReseller = Reseller::direct()->lockForUpdate()->findOrFail($oldResellerId);
            $newReseller = Reseller::direct()->lockForUpdate()->findOrFail($newResellerId);

            $oldReseller->due_amount += $oldAmount;
            $oldReseller->save();

            $newReseller->due_amount -= $newAmount;
            $newReseller->save();
        });

        return redirect()->route('direct-reseller-payments.index')->with('success', 'Payment updated successfully.');
    }

    /**
     * Cancel the specified payment.
     */
    public function cancel(ResellerPayment $resellerPayment)
    {
        $this->ensureRegularPayment($resellerPayment);

        if ($resellerPayment->status === 'cancelled') {
            return redirect()->route('direct-reseller-payments.index')->with('error', 'Payment is already cancelled.');
        }

        DB::transaction(function () use ($resellerPayment) {
            // Reverse the financial impact (Add back the amount to Due)
            $reseller = $resellerPayment->reseller;
            $reseller->increment('due_amount', $resellerPayment->amount);

            // Mark as cancelled
            $resellerPayment->update(['status' => 'cancelled']);
        });

        return redirect()->route('direct-reseller-payments.index')->with('success', 'Payment cancelled and amount returned to due balance.');
    }

    private function ensureRegularPayment(ResellerPayment $resellerPayment): void
    {
        abort_unless(
            $resellerPayment->reseller && $resellerPayment->reseller->reseller_type === Reseller::TYPE_DIRECT_RESELLER,
            404
        );
    }
}
