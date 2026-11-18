<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Partner;
use Illuminate\Console\Command;

class VerifyPartnerBalances extends Command
{
    protected $signature = 'partners:verify-balances {--partner= : Verify a specific partner by ID}';

    protected $description = 'Recalculate partner balances from source data and check that every invoice total matches its line items';

    public function handle(): int
    {
        $partnerId = $this->option('partner');

        if ($partnerId) {
            $partners = Partner::where('id', $partnerId)->get();
            if ($partners->isEmpty()) {
                $this->error("Partner with ID {$partnerId} not found.");
                return 1;
            }
        } else {
            $partners = Partner::all();
        }

        $rows = [];
        $allOk = true;

        foreach ($partners as $partner) {
            $outInvoices = $partner->invoices()->where('type', 'out')->sum('amount');
            $inInvoices = $partner->invoices()->where('type', 'in')->sum('amount');
            $uplata = $partner->transactions()->where('type', 'uplata')->sum('amount');
            $isplata = $partner->transactions()->where('type', 'isplata')->sum('amount');

            $calculated = $outInvoices + $isplata - $inInvoices - $uplata;
            $modelBalance = $partner->balance();

            $match = abs($calculated - $modelBalance) < 0.01;
            if (!$match) {
                $allOk = false;
            }

            $rows[] = [
                $partner->id,
                $partner->name,
                number_format($outInvoices, 2),
                number_format($inInvoices, 2),
                number_format($uplata, 2),
                number_format($isplata, 2),
                number_format($calculated, 2),
                $match ? 'OK' : 'MISMATCH',
            ];
        }

        $this->table(
            ['ID', 'Partner', 'Izlazne', 'Ulazne', 'Uplate', 'Isplate', 'Stanje', 'Status'],
            $rows
        );

        if ($allOk) {
            $this->info('All partner balances verified successfully.');
        } else {
            $this->error('Some partner balances have mismatches!');
        }

        $invoicesOk = $this->verifyInvoiceTotals($partners->pluck('id')->all());

        return $allOk && $invoicesOk ? 0 : 1;
    }

    /**
     * An invoice's amount is what the balance uses, so it must equal the sum of its line items.
     * Invoices without items (entered as a single amount) are skipped.
     *
     * @param  list<int>  $partnerIds
     */
    private function verifyInvoiceTotals(array $partnerIds): bool
    {
        $mismatches = Invoice::query()
            ->whereIn('partner_id', $partnerIds)
            ->has('items')
            ->withSum('items', 'total')
            ->get()
            ->filter(fn (Invoice $invoice) => abs((float) $invoice->amount - (float) $invoice->items_sum_total) >= 0.01);

        if ($mismatches->isEmpty()) {
            $this->info('All invoice totals match their line items.');
            return true;
        }

        $this->table(
            ['Faktura', 'Partner ID', 'Iznos', 'Zbir stavki'],
            $mismatches->map(fn (Invoice $invoice) => [
                $invoice->invoice_number,
                $invoice->partner_id,
                number_format((float) $invoice->amount, 2),
                number_format((float) $invoice->items_sum_total, 2),
            ])->all()
        );
        $this->error("{$mismatches->count()} invoice(s) do not match their line items!");

        return false;
    }
}
