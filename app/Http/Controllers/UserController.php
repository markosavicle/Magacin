<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $query = User::query();

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%") ;
            });
        }

        // Filter: approved
        if (request()->has('approved') && request('approved') !== '') {
            $query->where('approved', request('approved'));
        }

        // Filter: admin
        if (request()->has('is_admin') && request('is_admin') !== '') {
            $query->where('is_admin', request('is_admin'));
        }

        // Sort
        $sortBy = request('sort_by', 'name');
        $sortDirection = request('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->get();
        $pendingUsers = User::where('approved', false)->get();
        return view('users.index', compact('users', 'pendingUsers'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if (!Auth::user() || !Auth::user()->is_admin || $user->is_admin) {
            abort(403);
        }
        $user->delete();
        return redirect()->route('users.index')->with('status', 'User deleted successfully.');
    }
}
