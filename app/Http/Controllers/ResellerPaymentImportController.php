<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResellerPaymentTemplateExport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResellerPaymentImportController extends Controller
{
    public function show()
    {
        return view('resellers.payments.import');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ResellerPaymentTemplateExport, 'reseller_payment_template_' . date('Y-m-d') . '.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        // Pass the UploadedFile object directly so matching reader is detected by extension
        $data = Excel::toArray([], $request->file('file'));

        if (empty($data) || empty($data[0])) {
            return back()->with('error', 'The file is empty or invalid.');
        }

        $rows = array_slice($data[0], 1); // Skip header (Row 1)
        $previewData = [];
        $validRowsCount = 0;
        $hasErrors = false;

        foreach ($rows as $index => $row) {
            // Expected columns: 0=ID, 1=Name, 2=Due, 3=Amount, 4=Method, 5=Ref, 6=Date
            $amount = isset($row[3]) ? (float) $row[3] : 0;

            // Skip rows with no payment amount
            if ($amount <= 0) {
                continue;
            }

            $resellerId = isset($row[0]) ? $row[0] : null;
            $method = isset($row[4]) ? strtolower($row[4]) : 'cash';
            $reference = isset($row[5]) ? $row[5] : null;
            $dateStr = isset($row[6]) ? $row[6] : date('Y-m-d');

            // Validate Reseller
            $reseller = Reseller::regular()->find($resellerId);
            $errors = [];

            if (!$reseller) {
                $errors[] = "Invalid Reseller ID: $resellerId";
            }
            if (!in_array($method, ['cash', 'bank', 'other'])) {
                $errors[] = "Invalid Method: $method (Use cash, bank, or other)";
            }

            try {
                // Excel dates are sometimes integers (days since 1900-01-01)
                if (is_numeric($dateStr)) {
                    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr)->format('Y-m-d');
                } else {
                    $date = Carbon::parse($dateStr)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                $errors[] = "Invalid Date: $dateStr";
                $date = null;
            }

            if (!empty($errors)) {
                $hasErrors = true;
            } else {
                $validRowsCount++;
            }

            $previewData[] = [
                'reseller_id' => $resellerId,
                'reseller_name' => $reseller ? $reseller->name : 'Unknown',
                'current_due' => $reseller ? $reseller->due_amount : 0,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'date' => $date,
                'errors' => $errors,
            ];
        }

        // Cache data in session for final import.
        session(['import_preview_data' => $previewData]);

        return view('resellers.payments.import', compact('previewData', 'validRowsCount', 'hasErrors'));
    }

    public function store(Request $request)
    {
        $previewData = session('import_preview_data');

        if (!$previewData) {
            return redirect()->route('reseller-payments.import.show')->with('error', 'Session expired. Please upload the file again.');
        }

        $count = 0;

        DB::transaction(function () use ($previewData, &$count) {
            foreach ($previewData as $row) {
                if (!empty($row['errors'])) {
                    continue;
                }

                // Create Payment
                ResellerPayment::create([
                    'reseller_id' => $row['reseller_id'],
                    'amount' => $row['amount'],
                    'payment_method' => $row['method'],
                    'reference_id' => $row['reference'],
                    'payment_date' => $row['date'],
                    'status' => 'paid',
                ]);

                // Update Reseller Due
                $reseller = Reseller::regular()->find($row['reseller_id']);
                if ($reseller) {
                    $reseller->due_amount -= $row['amount'];
                    $reseller->save();
                }

                $count++;
            }
        });

        session()->forget('import_preview_data');

        return redirect()->route('reseller-payments.index')->with('success', "Successfully imported $count payments.");
    }
}
