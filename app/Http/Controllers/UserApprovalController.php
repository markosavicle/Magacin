<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    public function index(): View
    {
        $pendingUsers = User::where('approved', false)->get();
        return view('users.pending', compact('pendingUsers'));
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403);
        }
        $user->approved = true;
        $user->save();
        // Optionally notify user of approval
        return redirect()->route('users.index')->with('status', 'User approved successfully.');
    }
}
