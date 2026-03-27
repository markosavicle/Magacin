<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('partner');

        // Search functionality
        if ($request->has('search') && !empty(trim($request->search))) {
            $search = trim(strtolower($request->search));
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(note) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('partner', function($partnerQuery) use ($search) {
                      $partnerQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        // Filtering
        if ($request->has('type') && !empty(trim($request->type))) {
            $query->where('type', trim($request->type));
        }

        if ($request->has('status') && trim($request->status) !== '') {
            $query->where('status', trim($request->status));
        }

        if ($request->has('partner_id') && !empty(trim($request->partner_id))) {
            $query->where('partner_id', trim($request->partner_id));
        }

        // Date range filtering
        if ($request->has('date_from') && !empty(trim($request->date_from))) {
            $query->whereDate('date', '>=', trim($request->date_from));
        }

        if ($request->has('date_to') && !empty(trim($request->date_to))) {
            $query->whereDate('date', '<=', trim($request->date_to));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'date');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Ensure sort_by is a valid column
        $validSortColumns = ['invoice_number', 'date', 'amount', 'type', 'status', 'created_at'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'date';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        $invoices = $query->paginate(10)->appends($request->query());

        // Get partners for filter dropdown
        $partners = Partner::orderBy('name')->get();

        return view('invoices.index', compact('invoices', 'partners'));
    }

    public function create()
    {
        $partners = Partner::all();
        return view('invoices.create', compact('partners'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'date' => 'required|date',
            'status' => 'required|in:0,1',
            'note' => 'nullable|string|max:65535',
            'type' => 'required|in:in,out',
        ]);

        // Generiši automatski invoice_number, npr. INV0001, INV0002...
        $lastInvoice = Invoice::latest('id')->first();
        $nextNumber = $lastInvoice ? $lastInvoice->id + 1 : 1;
        $data['invoice_number'] = 'INV' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);


        $totalAmount = 0;

        // prvo izračunaj total
        if ($request->items) {
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }
        }

        $data['amount'] = $totalAmount;

        $invoice = Invoice::create($data);

        if ($request->items) {
            foreach ($request->items as $item) {

                if (!isset($item['name'], $item['quantity'], $item['price'])) continue;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }
        }

        return redirect()->route('invoices.index')->with('success', 'Račun dodat!');
    }

    public function edit(Invoice $invoice)
    {
        $partners = Partner::all();
        return view('invoices.edit', compact('invoice', 'partners'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number,' . $invoice->id,
            'date' => 'required|date',
            //ako zelimo da menjamo iznos mauelno, nema potrebe racunamo sa proizvodima 'amount' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
            'note' => 'nullable|string|max:65535',
            'type' => 'required|in:in,out',
        ]);

        $invoice->update($data);
        $totalAmount = 0;

    // obriši stare stavke
        $invoice->items()->delete();

        if ($request->items) {
            foreach ($request->items as $item) {
                if (!isset($item['name'], $item['quantity'], $item['price'])) continue;

                $total = $item['quantity'] * $item['price'];
                $totalAmount += $total;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $total,
                ]);
            }
        }

        $invoice->update([
            'amount' => $totalAmount
        ]);

        return redirect()->route('invoices.index')->with('success', 'Račun izmenjen!');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Račun obrisan!');
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }
}