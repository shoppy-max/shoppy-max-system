<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\Courier;
use App\Services\ResellerAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Rules\SriLankaMobile;
use App\Rules\SriLankaLandline;

class DirectResellerController extends Controller
{
    public function __construct(private ResellerAccountService $resellerAccounts)
    {
    }

    /**
     * Display a listing of direct resellers.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Reseller::direct()->with('userAccount');

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
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\DirectResellersExport($resellers), 'resellers.xlsx');
            }

            if ($request->input('export') === 'pdf') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.direct_resellers_pdf', compact('resellers'));
                $pdf->setPaper('a4', 'landscape');
                return $pdf->stream('resellers.pdf');
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
        $validated = $request->validate($this->rules());

        $loginDetails = DB::transaction(function () use ($validated) {
            $courierIds = $validated['couriers'] ?? [];
            unset($validated['couriers']);

            $data = $validated;
            $data['email'] = strtolower(trim($validated['email']));
            $data['reseller_type'] = Reseller::TYPE_DIRECT_RESELLER;
            $data['return_fee'] = 0;

            $reseller = Reseller::create($data);
            $reseller->couriers()->sync($courierIds);

            return $this->resellerAccounts->createAccount($reseller);
        });

        return redirect()->route('direct-resellers.index')
            ->with('success', 'Reseller and login account created successfully.')
            ->with('created_login', $loginDetails);
    }

    /**
     * Display the specified direct reseller.
     */
    public function show(Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);
        $directReseller->load('couriers', 'userAccount');

        $directReseller->load('userAccount');
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

        $validated = $request->validate($this->rules($directReseller));

        $loginDetails = DB::transaction(function () use ($validated, $directReseller) {
            $courierIds = $validated['couriers'] ?? [];
            unset($validated['couriers']);
            unset($validated['due_amount']);

            $data = $validated;
            $data['email'] = strtolower(trim($validated['email']));
            $data['reseller_type'] = Reseller::TYPE_DIRECT_RESELLER;
            $data['return_fee'] = 0;

            $directReseller->update($data);
            $directReseller->couriers()->sync($courierIds);

            return $this->resellerAccounts->syncAccount($directReseller->fresh());
        });

        $redirect = redirect()->route('direct-resellers.index')
            ->with('success', 'Reseller and login account updated successfully.');

        return $loginDetails ? $redirect->with('created_login', $loginDetails) : $redirect;
    }

    /**
     * Remove the specified direct reseller from storage.
     */
    public function destroy(Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);

        DB::transaction(function () use ($directReseller) {
            $this->resellerAccounts->retireAccount($directReseller);
            $directReseller->delete();
        });

        return redirect()->route('direct-resellers.index')->with('success', 'Reseller deleted successfully.');
    }

    public function resetPassword(Reseller $directReseller)
    {
        $this->ensureDirectReseller($directReseller);

        $loginDetails = DB::transaction(fn () => $this->resellerAccounts->resetPassword($directReseller));

        return redirect()->route('direct-resellers.index')
            ->with('success', 'Reseller password reset successfully.')
            ->with('created_login', $loginDetails);
    }

    private function ensureDirectReseller(Reseller $reseller): void
    {
        abort_unless($reseller->reseller_type === Reseller::TYPE_DIRECT_RESELLER, 404);
    }

    private function rules(?Reseller $reseller = null): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($reseller?->user_id),
                Rule::unique('resellers', 'email')->ignore($reseller?->id),
            ],
            'mobile' => ['required', 'string', new SriLankaMobile],
            'landline' => ['nullable', 'string', new SriLankaLandline],
            'address' => ['required', 'string'],
            'country' => ['required', 'string'],
            'province' => ['nullable', 'string'],
            'district' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'due_amount' => ['sometimes', 'numeric', 'min:0'],
            'couriers' => ['nullable', 'array'],
            'couriers.*' => ['integer', 'exists:couriers,id'],
        ];
    }
}
