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

   public function index()
{
    $partners = Partner::paginate(10); // 10 po stranici
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

public function show(Partner $partner)
{
    $invoices = $partner->invoices()->latest()->get();
    $transactions = $partner->transactions()->latest()->get();

    $balance = $partner->balance();

    return view('partners.show', compact(
        'partner',
        'invoices',
        'transactions',
        'balance'
    ));
}

}
