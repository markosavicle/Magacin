<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    private function getValidationRules()
    {
        return [
            'partner_id' => 'required|exists:partners,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:uplata,isplata',
            'date' => 'required|date',
            'note' => 'nullable|string|max:65535',
        ];
    }

    public function index(Request $request)
    {
        $query = Transaction::with('partner');

        // Search
        if ($request->has('search') && !empty(trim($request->search))) {
            $search = trim(strtolower($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(note) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('partner', function ($partnerQuery) use ($search) {
                      $partnerQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        // Filter by type
        if ($request->has('type') && !empty(trim($request->type))) {
            $query->where('type', trim($request->type));
        }

        // Filter by partner
        if ($request->has('partner_id') && !empty(trim($request->partner_id))) {
            $query->where('partner_id', trim($request->partner_id));
        }

        // Date range
        if ($request->has('date_from') && !empty(trim($request->date_from))) {
            $query->whereDate('date', '>=', trim($request->date_from));
        }
        if ($request->has('date_to') && !empty(trim($request->date_to))) {
            $query->whereDate('date', '<=', trim($request->date_to));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortDirection = $request->get('sort_direction', 'desc');

        $validSortColumns = ['date', 'amount', 'type', 'created_at'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'date';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        $transactions = $query->paginate(10)->appends($request->query());
        $partners = Partner::orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'partners'));
    }

    public function create(Request $request)
    {
        $partners = Partner::orderBy('name')->get();
        $selectedPartnerId = $request->get('partner_id');

        return view('transactions.create', compact('partners', 'selectedPartnerId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->getValidationRules());

        Transaction::create($data);

        if ($request->has('redirect_to_partner') && $request->redirect_to_partner) {
            return redirect()->route('partners.show', $data['partner_id'])->with('success', 'Transakcija dodata!');
        }

        return redirect()->route('transactions.index')->with('success', 'Transakcija dodata!');
    }

    public function edit(Transaction $transaction)
    {
        $partners = Partner::orderBy('name')->get();

        return view('transactions.edit', compact('transaction', 'partners'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate($this->getValidationRules());

        $transaction->update($data);

        return redirect()->route('transactions.index')->with('success', 'Transakcija izmenjena!');
    }

    public function destroy(Transaction $transaction)
    {
        $partnerId = $transaction->partner_id;
        $transaction->delete();

        if (request()->has('redirect_to_partner') && request()->redirect_to_partner) {
            return redirect()->route('partners.show', $partnerId)->with('success', 'Transakcija obrisana!');
        }

        return redirect()->route('transactions.index')->with('success', 'Transakcija obrisana!');
    }
}
