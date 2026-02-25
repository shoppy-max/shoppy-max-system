<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResellerTargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ResellerTarget::query()
            ->with('reseller')
            ->whereHas('reseller', fn ($q) => $q->regular());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ref_id', 'like', "%{$search}%")
                  ->orWhereHas('reseller', function ($resellerQuery) use ($search) {
                      $resellerQuery->where('name', 'like', "%{$search}%")
                          ->orWhere('business_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        $targets = $query->latest()->paginate(10);

        return view('resellers.targets.index', compact('targets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $resellers = Reseller::regular()->orderBy('name')->get();

        return view('resellers.targets.create', compact('resellers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reseller_id' => [
                'required',
                Rule::exists('resellers', 'id')->where('reseller_type', Reseller::TYPE_RESELLER),
            ],
            'target_type' => 'required|in:daily,weekly,monthly',
            'target_pcs_qty' => 'required|integer|min:1',
            'target_completed_price' => 'nullable|numeric|min:0',
            'with_completed_price' => 'nullable|numeric|min:0',
            'return_order_target_price' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'ref_id' => 'nullable|string|max:255',
        ]);

        ResellerTarget::create($validated);

        return redirect()->route('reseller-targets.index')
            ->with('success', 'Target created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ResellerTarget $resellerTarget)
    {
        $this->ensureRegularTarget($resellerTarget);

        $resellers = Reseller::regular()->orderBy('name')->get();

        return view('resellers.targets.edit', compact('resellerTarget', 'resellers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ResellerTarget $resellerTarget)
    {
        $this->ensureRegularTarget($resellerTarget);

        $validated = $request->validate([
            'reseller_id' => [
                'required',
                Rule::exists('resellers', 'id')->where('reseller_type', Reseller::TYPE_RESELLER),
            ],
            'target_type' => 'required|in:daily,weekly,monthly',
            'target_pcs_qty' => 'required|integer|min:1',
            'target_completed_price' => 'nullable|numeric|min:0',
            'with_completed_price' => 'nullable|numeric|min:0',
            'return_order_target_price' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'ref_id' => 'nullable|string|max:255',
        ]);

        $resellerTarget->update($validated);

        return redirect()->route('reseller-targets.index')
            ->with('success', 'Target updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ResellerTarget $resellerTarget)
    {
        $this->ensureRegularTarget($resellerTarget);

        $resellerTarget->delete();

        return redirect()->route('reseller-targets.index')
            ->with('success', 'Target deleted successfully.');
    }

    private function ensureRegularTarget(ResellerTarget $resellerTarget): void
    {
        abort_unless(
            $resellerTarget->reseller && $resellerTarget->reseller->reseller_type === Reseller::TYPE_RESELLER,
            404
        );
    }
}
