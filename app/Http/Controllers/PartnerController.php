<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    private function getValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:65535',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
        ];
    }

   public function index(Request $request)
{
    $query = Partner::query();

    // Search functionality
    if ($request->has('search') && !empty(trim($request->search))) {
        $search = trim(strtolower($request->search));
        $query->where(function($q) use ($search) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(type) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(phone_number) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(city) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(contact_person) LIKE ?', ["%{$search}%"])
              ->orWhereRaw('LOWER(vat_number) LIKE ?', ["%{$search}%"]);
        });
    }

    // Filtering
    if ($request->has('type') && !empty(trim($request->type))) {
        $query->where('type', trim($request->type));
    }

    if ($request->has('city') && !empty(trim($request->city))) {
        $query->where('city', trim($request->city));
    }

    // Sorting
    $sortBy = $request->get('sort_by', 'name');
    $sortDirection = $request->get('sort_direction', 'asc');

    // Ensure sort_by is a valid column
    $validSortColumns = ['name', 'type', 'email', 'phone', 'city', 'created_at'];
    if (!in_array($sortBy, $validSortColumns)) {
        $sortBy = 'name';
    }

    if (!in_array($sortDirection, ['asc', 'desc'])) {
        $sortDirection = 'asc';
    }

    $query->orderBy($sortBy, $sortDirection);

    $partners = $query->paginate(10)->appends($request->query());

    return view('partners.index', compact('partners'));
}

public function create()
{
    return view('partners.create');
}

public function store(Request $request)
{
    $data = $request->validate($this->getValidationRules());

    Partner::create($data);
    return redirect()->route('partners.index')->with('success', 'Partner dodat!');
}

public function edit(Partner $partner)
{
    return view('partners.edit', compact('partner'));
}

public function update(Request $request, Partner $partner)
{
    $data = $request->validate($this->getValidationRules());   
    $partner->update($data);
    return redirect()->route('partners.index')->with('success', 'Partner izmenjen!');
}

public function destroy(Partner $partner)
{
    $partner->delete();
    return redirect()->route('partners.index')->with('success', 'Partner obrisan!');
}

public function show(Request $request, Partner $partner)
    {
        // Sort invoices
        $invoiceSortBy = $request->get('invoice_sort_by', 'created_at');
        $invoiceSortDirection = $request->get('invoice_sort_direction', 'desc');
        $validInvoiceSortColumns = ['invoice_number', 'type', 'amount', 'status', 'created_at'];
        if (!in_array($invoiceSortBy, $validInvoiceSortColumns)) {
            $invoiceSortBy = 'created_at';
        }
        if (!in_array($invoiceSortDirection, ['asc', 'desc'])) {
            $invoiceSortDirection = 'desc';
        }

        $invoices = $partner->invoices()->orderBy($invoiceSortBy, $invoiceSortDirection)->get();

        // Sort transactions
        $transactionSortBy = $request->get('transaction_sort_by', 'created_at');
        $transactionSortDirection = $request->get('transaction_sort_direction', 'desc');
        $validTransactionSortColumns = ['type', 'amount', 'created_at'];
        if (!in_array($transactionSortBy, $validTransactionSortColumns)) {
            $transactionSortBy = 'created_at';
        }
        if (!in_array($transactionSortDirection, ['asc', 'desc'])) {
            $transactionSortDirection = 'desc';
        }

        $transactions = $partner->transactions()->orderBy($transactionSortBy, $transactionSortDirection)->get();

    $balance = $partner->balance();

    return view('partners.show', compact(
        'partner',
        'invoices',
        'transactions',
        'balance'
    ));
}

}
