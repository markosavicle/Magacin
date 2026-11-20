<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InvoicePaymentStatus;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(Partner $partner, string $number, string $type, float $amount, string $date): Invoice
    {
        return Invoice::create([
            'partner_id' => $partner->id,
            'invoice_number' => $number,
            'type' => $type,
            'amount' => $amount,
            'date' => $date,
        ]);
    }

    private function payment(Partner $partner, string $type, float $amount, string $date): Transaction
    {
        return Transaction::create(['partner_id' => $partner->id, 'type' => $type, 'amount' => $amount, 'date' => $date]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_empty_dashboard_renders_with_quick_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Još nema faktura')
            ->assertSee('Još nema uplata')
            ->assertSee(route('invoices.create'), false)
            ->assertSee(route('transactions.create'), false)
            ->assertSee(route('partners.index'), false);
    }

    public function test_kpis_statuses_and_recent_activity(): void
    {
        $customer = Partner::create(['name' => 'Kupac Test d.o.o.', 'type' => 'kupac']);
        $supplier = Partner::create(['name' => 'Dobavljač Test d.o.o.', 'type' => 'dobavljac']);

        $first = $this->invoice($customer, 'T-001', 'out', 1000, '2026-01-10');
        $second = $this->invoice($customer, 'T-002', 'out', 500, '2026-02-10');
        $incoming = $this->invoice($supplier, 'T-003', 'in', 800, '2026-02-15');
        $this->payment($customer, 'uplata', 1200, '2026-02-20');

        $statuses = app(InvoicePaymentStatus::class)->compute();
        $this->assertSame(InvoicePaymentStatus::PAID, $statuses[$first->id]['status']);
        $this->assertSame(InvoicePaymentStatus::PARTIAL, $statuses[$second->id]['status']);
        $this->assertEquals(300, $statuses[$second->id]['open']);
        $this->assertSame(InvoicePaymentStatus::UNPAID, $statuses[$incoming->id]['status']);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Partneri', '2', 'Kupaca: 1', 'Dobavljača: 1'])
            ->assertSeeInOrder(['Ukupno fakturisano', '1,500.00 RSD', 'Izlaznih faktura: 2'])
            ->assertSeeInOrder(['Naplaćeno', '1,200.00 RSD', '80%'])
            ->assertSeeInOrder(['Otvoreno potraživanje', '300.00 RSD', 'Neplaćenih faktura: 1', 'Dugujemo dobavljačima: 800.00 RSD'])
            // newest invoice first, each with its derived status and a link
            ->assertSeeInOrder(['T-003', 'Neplaćena', 'T-002', 'Delimično plaćena', 'T-001', 'Plaćena'])
            ->assertSee(route('invoices.show', $first), false)
            ->assertSee('Uplata');
    }

    public function test_dashboard_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);

        $latest = Invoice::orderByDesc('date')->orderByDesc('id')->first();
        $latestPayment = Transaction::orderByDesc('date')->orderByDesc('id')->first();
        $invoiced = number_format((float) Invoice::where('type', 'out')->sum('amount'), 2) . ' RSD';
        $collected = number_format((float) Transaction::where('type', 'uplata')->sum('amount'), 2) . ' RSD';
        $receivable = number_format(Partner::all()->sum(fn (Partner $p) => max(0, $p->balance())), 2) . ' RSD';

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Partneri', '12'])
            ->assertSee($invoiced)
            ->assertSee($collected)
            ->assertSeeInOrder(['Otvoreno potraživanje', $receivable])
            ->assertSee($latest->invoice_number)
            ->assertSee(route('invoices.show', $latest), false)
            ->assertSee(route('transactions.edit', $latestPayment), false);
    }
}
