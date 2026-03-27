<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = History::with('user');

        // Search
        if ($request->has('search') && !empty(trim($request->search))) {
            $search = trim(strtolower($request->search));
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(action) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        // Filter by action
        if ($request->has('action') && !empty(trim($request->action))) {
            $query->where('action', trim($request->action));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $validSortColumns = ['action', 'description', 'created_at'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $logs = $query->orderBy($sortBy, $sortDirection)->paginate(15);

        return view('history.index', compact('logs'));
    }
}
