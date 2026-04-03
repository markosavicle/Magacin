<?php

namespace App\Console\Commands;

use App\Models\Partner;
use Illuminate\Console\Command;

class VerifyPartnerBalances extends Command
{
    protected $signature = 'partners:verify-balances {--partner= : Verify a specific partner by ID}';

    protected $description = 'Recalculate and verify partner balances from source data';

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

        return $allOk ? 0 : 1;
    }
}
