<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
   public function index()
{
    $partners = Partner::all();
    return view('partners.index', compact('partners'));
}

public function create()
{
    return view('partners.create');
}

public function store(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'nullable|string|max:100',
        'phone' => 'nullable|string|max:50',
        'note' => 'nullable|string|max:1000',
    ]);

    Partner::create($data);
    return redirect()->route('partners.index')->with('success', 'Partner dodat!');
}

public function edit(Partner $partner)
{
    return view('partners.edit', compact('partner'));
}

public function update(Request $request, Partner $partner)
{
    $partner->update($request->all());
    return redirect()->route('partners.index')->with('success', 'Partner izmenjen!');
}

public function destroy(Partner $partner)
{
    $partner->delete();
    return redirect()->route('partners.index')->with('success', 'Partner obrisan!');
}
}
