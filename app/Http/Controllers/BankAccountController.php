<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('holder_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $accounts = $query
            ->withCount('courierPayments')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => BankAccount::count(),
            'active' => BankAccount::where('is_active', true)->count(),
            'used_in_payments' => BankAccount::whereHas('courierPayments')->count(),
        ];

        return view('bank-accounts.index', compact('accounts', 'stats'));
    }

    public function create()
    {
        return view('bank-accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255', Rule::unique('bank_accounts', 'account_number')],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['Bank', 'Mobile Wallet', 'Cash', 'Other'])],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        BankAccount::create($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account created successfully.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255', Rule::unique('bank_accounts', 'account_number')->ignore($bankAccount->id)],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['Bank', 'Mobile Wallet', 'Cash', 'Other'])],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->courierPayments()->exists()) {
            return back()->with('error', 'This account is used in courier payments and cannot be deleted.');
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account deleted successfully.');
    }
}
