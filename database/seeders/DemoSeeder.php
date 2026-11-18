<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fictional data for a small food & drinks wholesaler, for demos and screenshots:
 *   php artisan db:seed --class=DemoSeeder
 *
 * Every company, person, PIB, phone number and address here is made up. PIBs are in the
 * 100000101-100000199 range and e-mails use the reserved .example domain, so nothing can
 * reach a real business. The data is the same on every run (fixed random seed and dates).
 * Runs once: if the demo partners already exist, it does nothing.
 */
class DemoSeeder extends Seeder
{
    public const FIRST_PIB = 100000101;

    /** [name, type, city, contact person] — kupac = we sell to them, dobavljac = we buy from them. */
    private const PARTNERS = [
        ['Mini market Lipa d.o.o.', 'kupac', 'Niš', 'Jelena Stojanović'],
        ['Market Kod Mosta d.o.o.', 'kupac', 'Niš', 'Dragan Ilić'],
        ['Prodavnica Zora', 'kupac', 'Leskovac', 'Milica Pavlović'],
        ['Kafe bar Ugao', 'kupac', 'Niš', 'Nemanja Kostić'],
        ['Restoran Stara Vodenica d.o.o.', 'kupac', 'Prokuplje', 'Ivana Đorđević'],
        ['Pekara Zrno d.o.o.', 'kupac', 'Aleksinac', 'Stefan Marković'],
        ['Market Plus 24 d.o.o.', 'kupac', 'Vranje', 'Ana Mitić'],
        ['Hotel Sunčani Breg d.o.o.', 'kupac', 'Sokobanja', 'Marija Nikolić'],
        ['Mlekara Primer d.o.o.', 'dobavljac', 'Pirot', 'Zoran Petrović'],
        ['Vinarija Brdo Primer d.o.o.', 'dobavljac', 'Knjaževac', 'Milan Jovanović'],
        ['Pivara Primer a.d.', 'dobavljac', 'Zaječar', 'Saša Tasić'],
        ['Distribucija Voda Primer d.o.o.', 'dobavljac', 'Beograd', 'Tamara Lazić'],
    ];

    /** [item, unit price in RSD] for what we sell to customers. */
    private const SALES_ITEMS = [
        ['Mleko 2,8% 1l (pak. 12)', 1620.00],
        ['Jogurt 1,5l (pak. 6)', 1290.00],
        ['Sir beli 1kg', 890.00],
        ['Pivo 0,5l limenka (pak. 24)', 2760.00],
        ['Vino crveno 0,75l (gajba 6)', 4140.00],
        ['Voda negazirana 1,5l (pak. 6)', 390.00],
        ['Voda gazirana 1,5l (pak. 6)', 420.00],
        ['Sok od jabuke 1l (pak. 12)', 2280.00],
        ['Kafa mlevena 500g', 980.00],
    ];

    /** Purchase price is 78% of the selling price. */
    private const PURCHASE_FACTOR = 0.78;

    public function run(): void
    {
        if (Partner::where('vat_number', (string) self::FIRST_PIB)->exists()) {
            $this->command?->info('DemoSeeder: demo data already present, nothing to do.');
            return;
        }

        mt_srand(2026);
        $start = CarbonImmutable::parse('2026-05-04');

        DB::transaction(function () use ($start) {
            foreach (self::PARTNERS as $i => [$name, $type, $city, $contact]) {
                $partner = Partner::create([
                    'name' => $name,
                    'type' => $type,
                    'city' => $city,
                    'contact_person' => $contact,
                    'vat_number' => (string) (self::FIRST_PIB + $i),
                    'address' => 'Primer ulica ' . ($i + 1),
                    'phone' => sprintf('+381 60 000 %04d', $i + 1),
                    'email' => 'kontakt' . ($i + 1) . '@primer.example',
                    'note' => 'Demo partner, izmišljeni podaci.',
                ]);

                $this->seedInvoices($partner, $start);
            }
        });

        $this->command?->info(sprintf(
            'DemoSeeder: %d partners, %d invoices, %d payments.',
            Partner::count(), Invoice::count(), Transaction::count()
        ));
    }

    private function seedInvoices(Partner $partner, CarbonImmutable $start): void
    {
        $isCustomer = $partner->type === 'kupac';
        $invoiceType = $isCustomer ? 'out' : 'in';
        $count = $isCustomer ? mt_rand(8, 14) : mt_rand(5, 8);

        for ($n = 0; $n < $count; $n++) {
            $date = $start->addDays(intdiv(180 * $n, $count) + mt_rand(0, 6));
            $lines = $this->pickLines($isCustomer ? 1.0 : self::PURCHASE_FACTOR, $isCustomer ? mt_rand(2, 5) : mt_rand(1, 3), $isCustomer ? [2, 20] : [40, 150]);
            $amount = round(array_sum(array_column($lines, 'total')), 2);

            $invoice = Invoice::create([
                'partner_id' => $partner->id,
                'invoice_number' => 'INV' . str_pad((string) ((Invoice::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT),
                'date' => $date->toDateString(),
                'amount' => $amount,
                'type' => $invoiceType,
                'note' => null,
            ]);

            foreach ($lines as $line) {
                InvoiceItem::create(['invoice_id' => $invoice->id] + $line);
            }

            // Customers pay most invoices about two weeks later; we pay suppliers within a month.
            $paid = $isCustomer ? mt_rand(1, 100) <= 80 : mt_rand(1, 100) <= 70;
            if ($paid && $date->addDays(30)->lessThan($start->addDays(180))) {
                Transaction::create([
                    'partner_id' => $partner->id,
                    'amount' => $amount,
                    'type' => $isCustomer ? 'uplata' : 'isplata',
                    'date' => $date->addDays($isCustomer ? mt_rand(7, 21) : mt_rand(15, 30))->toDateString(),
                    'note' => 'Po fakturi ' . $invoice->invoice_number,
                ]);
            }
        }
    }

    /** @return list<array{name: string, quantity: float, price: float, total: float}> */
    private function pickLines(float $priceFactor, int $lines, array $quantityRange): array
    {
        $keys = array_rand(self::SALES_ITEMS, $lines);
        $result = [];
        foreach ((array) $keys as $key) {
            [$name, $price] = self::SALES_ITEMS[$key];
            $price = round($price * $priceFactor, 2);
            $quantity = (float) mt_rand(...$quantityRange);
            $result[] = ['name' => $name, 'quantity' => $quantity, 'price' => $price, 'total' => round($quantity * $price, 2)];
        }
        return $result;
    }
}
