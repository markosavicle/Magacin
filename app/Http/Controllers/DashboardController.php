<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Transaction;
use App\Services\InvoicePaymentStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(InvoicePaymentStatus $paymentStatus): View
    {
        $partnersByType = Partner::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $invoiceTotals = Invoice::query()
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $paymentTotals = Transaction::query()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        // Same formula as Partner::balance(), for every partner in two queries.
        // Positive = the partner owes us, negative = we owe the partner.
        $balances = [];
        foreach (Invoice::query()->selectRaw('partner_id, type, SUM(amount) as total')->groupBy('partner_id', 'type')->get() as $row) {
            $balances[$row->partner_id] = ($balances[$row->partner_id] ?? 0)
                + ($row->type === 'out' ? 1 : -1) * (float) $row->total;
        }
        foreach (Transaction::query()->selectRaw('partner_id, type, SUM(amount) as total')->groupBy('partner_id', 'type')->get() as $row) {
            $balances[$row->partner_id] = ($balances[$row->partner_id] ?? 0)
                + ($row->type === 'isplata' ? 1 : -1) * (float) $row->total;
        }

        $statuses = $paymentStatus->compute();
        $openOutgoing = Invoice::query()
            ->where('type', 'out')
            ->pluck('id')
            ->filter(fn ($id) => ($statuses[$id]['status'] ?? null) !== InvoicePaymentStatus::PAID)
            ->count();

        $invoicedOut = (float) ($invoiceTotals['out']->total ?? 0);
        $collected = (float) ($paymentTotals['uplata'] ?? 0);

        return view('dashboard', [
            'kpi' => [
                'partners' => (int) $partnersByType->sum(),
                'customers' => (int) ($partnersByType['kupac'] ?? 0),
                'suppliers' => (int) ($partnersByType['dobavljac'] ?? 0),
                'invoiced' => $invoicedOut,
                'invoicedCount' => (int) ($invoiceTotals['out']->count ?? 0),
                'collected' => $collected,
                'collectedShare' => $invoicedOut > 0 ? round($collected / $invoicedOut * 100) : null,
                'receivable' => round(array_sum(array_filter($balances, fn ($b) => $b > 0)), 2),
                'payable' => round(-array_sum(array_filter($balances, fn ($b) => $b < 0)), 2),
                'openInvoices' => $openOutgoing,
            ],
            'recentInvoices' => Invoice::with('partner')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'recentPayments' => Transaction::with('partner')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'statuses' => $statuses,
        ]);
    }
}
