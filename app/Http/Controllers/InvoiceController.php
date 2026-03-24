<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Partner;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('partner')->paginate(10);
        return view('invoices.index', compact('invoices'));
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
            'amount' => 'required|numeric|min:0',
            'status' => 'required|string|max:50',
            'note' => 'nullable|string|max:65535',
        ]);

        // Generiši automatski invoice_number, npr. INV0001, INV0002...
        $lastInvoice = Invoice::latest('id')->first();
        $nextNumber = $lastInvoice ? $lastInvoice->id + 1 : 1;
        $data['invoice_number'] = 'INV' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        Invoice::create($data);

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
            'amount' => 'required|numeric|min:0',
            'status' => 'required|string|max:50',
            'note' => 'nullable|string|max:65535',
        ]);

        $invoice->update($data);
        return redirect()->route('invoices.index')->with('success', 'Račun izmenjen!');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Račun obrisan!');
    }
}