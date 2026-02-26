<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\Courier;
use Illuminate\Http\Request;
use App\Rules\SriLankaMobile;
use App\Rules\SriLankaLandline;

class DirectResellerController extends Controller
{
    /**
     * Display a listing of direct resellers.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Reseller::direct();

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
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DirectResellersExport($resellers), 'direct_resellers.xlsx');
            }

            if ($request->input('export') === 'pdf') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.direct_resellers_pdf', compact('resellers'));
                $pdf->setPaper('a4', 'landscape');
                return $pdf->stream('direct_resellers.pdf');
            }
        }

        $resellers = $query->paginate(10);

        // Stats for Dashboard Cards
        $totalResellers = Reseller::direct()->count();
        $totalDue = Reseller::direct()->sum('due_amount');
        $activeResellers = Reseller::direct()->where('due_amount', '>', 0)->count();

        return view('contacts.direct_resellers.index', compact('resellers', 'totalResellers', 'totalDue', 'activeResellers'));
    }

    /**
     * Show the form for creating a new direct reseller.
     */
    public function create()
    {
        $countries = config('locations.countries');
        $slData = config('locations.sri_lanka');
        $couriers = Courier::orderBy('name')->get(['id', 'name', 'phone']);

        return view('contacts.direct_resellers.create', compact('countries', 'slData', 'couriers'));
    }

    /**
     * Store a newly created direct reseller in storage.
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
            'couriers' => 'nullable|array',
            'couriers.*' => 'integer|exists:couriers,id',
        ]);

        $courierIds = $validated['couriers'] ?? [];
        unset($validated['couriers']);

        $data = $validated;
        $data['reseller_type'] = Reseller::TYPE_DIRECT_RESELLER;
        $data['return_fee'] = 0;

        $reseller = Reseller::create($data);
        $reseller->couriers()->sync($courierIds);

        return redirect()->route('direct-resellers.index')->with('success', 'Direct reseller created successfully.');
    }

    /**
     * Display the specified direct reseller.
     */
    public function show(Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);
        $directReseller->load('couriers');

        $reseller = $directReseller;

        return view('contacts.direct_resellers.show', compact('reseller'));
    }

    /**
     * Show the form for editing the specified direct reseller.
     */
    public function edit(Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);

        $reseller = $directReseller;
        $countries = config('locations.countries');
        $slData = config('locations.sri_lanka');
        $couriers = Courier::orderBy('name')->get(['id', 'name', 'phone']);

        return view('contacts.direct_resellers.edit', compact('reseller', 'countries', 'slData', 'couriers'));
    }

    /**
     * Update the specified direct reseller in storage.
     */
    public function update(Request $request, Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);

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
            'couriers' => 'nullable|array',
            'couriers.*' => 'integer|exists:couriers,id',
        ]);

        $courierIds = $validated['couriers'] ?? [];
        unset($validated['couriers']);

        $data = $validated;
        $data['reseller_type'] = Reseller::TYPE_DIRECT_RESELLER;
        $data['return_fee'] = 0;

        $directReseller->update($data);
        $directReseller->couriers()->sync($courierIds);

        return redirect()->route('direct-resellers.index')->with('success', 'Direct reseller updated successfully.');
    }

    /**
     * Remove the specified direct reseller from storage.
     */
    public function destroy(Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);

        $directReseller->delete();

        return redirect()->route('direct-resellers.index')->with('success', 'Direct reseller deleted successfully.');
    }

    private function ensureDirectReseller(Reseller $reseller): void
    {
        abort_unless($reseller->reseller_type === Reseller::TYPE_DIRECT_RESELLER, 404);
    }
}
