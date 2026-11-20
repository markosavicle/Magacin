<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Transaction;

/**
 * Works out whether invoices are paid. Payments are not linked to a specific invoice, so a
 * partner's payments are applied to that partner's invoices oldest first (FIFO):
 *   - "uplata" (partner pays us) settles our outgoing invoices ("out"),
 *   - "isplata" (we pay the partner) settles their incoming invoices ("in").
 *
 * Amounts are handled in whole para (cents) so that rounding never flips a status.
 */
class InvoicePaymentStatus
{
    public const PAID = 'paid';
    public const PARTIAL = 'partial';
    public const UNPAID = 'unpaid';

    /** Which payment type settles which invoice type. */
    private const SETTLED_BY = ['out' => 'uplata', 'in' => 'isplata'];

    /**
     * @param  array<int>|null  $partnerIds  limit to these partners (null = all partners)
     * @return array<int, array{status: string, paid: float, open: float}> keyed by invoice id
     */
    public function compute(?array $partnerIds = null): array
    {
        $invoices = Invoice::query()
            ->select(['id', 'partner_id', 'type', 'amount', 'date'])
            ->when($partnerIds !== null, fn ($q) => $q->whereIn('partner_id', $partnerIds))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // Money still available to settle invoices, per partner and payment type.
        $available = [];
        Transaction::query()
            ->selectRaw('partner_id, type, SUM(amount) as total')
            ->when($partnerIds !== null, fn ($q) => $q->whereIn('partner_id', $partnerIds))
            ->groupBy('partner_id', 'type')
            ->get()
            ->each(function ($row) use (&$available) {
                $available[$row->partner_id][$row->type] = self::toCents($row->total);
            });

        $result = [];
        foreach ($invoices as $invoice) {
            $paymentType = self::SETTLED_BY[$invoice->type] ?? null;
            $amount = self::toCents($invoice->amount);
            $left = $paymentType ? ($available[$invoice->partner_id][$paymentType] ?? 0) : 0;
            $paid = max(0, min($amount, $left));

            if ($paymentType) {
                $available[$invoice->partner_id][$paymentType] = $left - $paid;
            }

            $result[$invoice->id] = [
                'status' => match (true) {
                    $paid >= $amount => self::PAID,
                    $paid > 0 => self::PARTIAL,
                    default => self::UNPAID,
                },
                'paid' => $paid / 100,
                'open' => ($amount - $paid) / 100,
            ];
        }

        return $result;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::PAID => 'Plaćena',
            self::PARTIAL => 'Delimično plaćena',
            default => 'Neplaćena',
        };
    }

    private static function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
