<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\Courier;
use Illuminate\Http\Request;
use App\Rules\SriLankaMobile;
use App\Rules\SriLankaLandline;

class ResellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Reseller::regular();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'business_name', 'mobile', 'email', 'due_amount'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        if ($request->has('export')) {
            $resellers = $query->get();

            if ($request->input('export') === 'excel') {
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ResellersExport($resellers), 'resellers.xlsx');
            }

            if ($request->input('export') === 'pdf') {
                $reportTitle = 'Reseller List';
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.resellers_pdf', compact('resellers', 'reportTitle'));
                $pdf->setPaper('a4', 'landscape');
                return $pdf->stream('resellers.pdf');
            }
        }

        $resellers = $query->paginate(10);

        // Stats for Dashboard Cards
        $totalResellers = Reseller::regular()->count();
        $totalDue = Reseller::regular()->sum('due_amount');
        $activeResellers = Reseller::regular()->where('due_amount', '>', 0)->count(); // Example metric

        return view('contacts.resellers.index', compact('resellers', 'totalResellers', 'totalDue', 'activeResellers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $countries = config('locations.countries');
        $slData = config('locations.sri_lanka');
        $couriers = Courier::orderBy('name')->get(['id', 'name', 'phone']);
        return view('contacts.resellers.create', compact('countries', 'slData', 'couriers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => ['required', 'string', new SriLankaMobile],
            'landline' => ['nullable', 'string', new SriLankaLandline],
            'address' => 'required|string',
            'country' => 'required|string',
            'province' => 'nullable|string',
            'district' => 'nullable|string',
            'city' => 'nullable|string',
            'due_amount' => 'numeric|min:0',
            'return_fee' => 'required|numeric|min:0',
            'couriers' => 'nullable|array',
            'couriers.*' => 'integer|exists:couriers,id',
        ]);

        $courierIds = $validated['couriers'] ?? [];
        unset($validated['couriers']);

        $data = $validated;
        $data['reseller_type'] = Reseller::TYPE_RESELLER;
        $data['return_fee'] = round((float) $validated['return_fee'], 2);

        $reseller = Reseller::create($data);
        $reseller->couriers()->sync($courierIds);

        return redirect()->route('resellers.index')->with('success', 'Reseller created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reseller $reseller)
    {
        $this->ensureRegularReseller($reseller);
        $reseller->load('couriers');

        return view('contacts.resellers.show', compact('reseller'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reseller $reseller)
    {
        $this->ensureRegularReseller($reseller);

        $countries = config('locations.countries');
        $slData = config('locations.sri_lanka');
        $couriers = Courier::orderBy('name')->get(['id', 'name', 'phone']);
        return view('contacts.resellers.edit', compact('reseller', 'countries', 'slData', 'couriers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reseller $reseller)
    {
        $this->ensureRegularReseller($reseller);

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => ['required', 'string', new SriLankaMobile],
            'landline' => ['nullable', 'string', new SriLankaLandline],
            'address' => 'required|string',
            'country' => 'required|string',
            'province' => 'nullable|string',
            'district' => 'nullable|string',
            'city' => 'nullable|string',
            'return_fee' => 'required|numeric|min:0',
            'couriers' => 'nullable|array',
            'couriers.*' => 'integer|exists:couriers,id',
        ]);

        $courierIds = $validated['couriers'] ?? [];
        unset($validated['couriers']);

        $data = $validated;
        $data['reseller_type'] = Reseller::TYPE_RESELLER;
        $data['return_fee'] = round((float) $validated['return_fee'], 2);

        $reseller->update($data);
        $reseller->couriers()->sync($courierIds);

        return redirect()->route('resellers.index')->with('success', 'Reseller updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reseller $reseller)
    {
        $this->ensureRegularReseller($reseller);

        $reseller->delete();

        return redirect()->route('resellers.index')->with('success', 'Reseller deleted successfully.');
    }

    private function ensureRegularReseller(Reseller $reseller): void
    {
        abort_unless($reseller->reseller_type === Reseller::TYPE_RESELLER, 404);
    }
}
