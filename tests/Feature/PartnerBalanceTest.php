<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function partnerWithHistory(): Partner
    {
        $partner = Partner::create(['name' => 'Test Kupac d.o.o.', 'type' => 'kupac', 'vat_number' => '100000999']);

        $this->invoice($partner, 'INV0001', 'out', [[10, 150.00], [2, 500.00]]); // 2.500,00 we invoiced them
        $this->invoice($partner, 'INV0002', 'in', [[1, 400.00]]);                // 400,00 they invoiced us
        Transaction::create(['partner_id' => $partner->id, 'amount' => 1000, 'type' => 'uplata', 'date' => '2026-06-10']);
        Transaction::create(['partner_id' => $partner->id, 'amount' => 100, 'type' => 'isplata', 'date' => '2026-06-12']);

        return $partner;
    }

    /** @param list<array{0: float|int, 1: float}> $lines [quantity, price] */
    private function invoice(Partner $partner, string $number, string $type, array $lines): Invoice
    {
        $amount = array_sum(array_map(fn ($l) => $l[0] * $l[1], $lines));
        $invoice = Invoice::create([
            'partner_id' => $partner->id, 'invoice_number' => $number, 'date' => '2026-06-01',
            'amount' => $amount, 'type' => $type,
        ]);
        foreach ($lines as [$quantity, $price]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id, 'name' => 'Artikal', 'quantity' => $quantity,
                'price' => $price, 'total' => $quantity * $price,
            ]);
        }

        return $invoice;
    }

    public function test_balance_is_outgoing_plus_payouts_minus_incoming_minus_payments(): void
    {
        // 2500 (out) + 100 (isplata) - 400 (in) - 1000 (uplata)
        $this->assertEqualsWithDelta(1200.00, $this->partnerWithHistory()->balance(), 0.001);
    }

    public function test_verify_command_passes_on_consistent_data(): void
    {
        $this->partnerWithHistory();

        $this->artisan('partners:verify-balances')
            ->expectsOutput('All partner balances verified successfully.')
            ->expectsOutput('All invoice totals match their line items.')
            ->assertExitCode(0);
    }

    public function test_verify_command_flags_an_invoice_that_does_not_match_its_items(): void
    {
        $partner = $this->partnerWithHistory();
        // Simulates an amount edited directly in the database.
        Invoice::where('invoice_number', 'INV0001')->update(['amount' => 2600]);

        $this->artisan('partners:verify-balances', ['--partner' => $partner->id])
            ->expectsOutput('1 invoice(s) do not match their line items!')
            ->assertExitCode(1);
    }

    public function test_verify_command_rejects_an_unknown_partner(): void
    {
        $this->artisan('partners:verify-balances', ['--partner' => 999])->assertExitCode(1);
    }

    public function test_partner_statement_and_invoice_export_as_pdf(): void
    {
        $partner = $this->partnerWithHistory();
        $user = User::factory()->create(['approved' => true]);

        $this->actingAs($user)->get(route('partners.exportPdf', $partner))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $invoice = Invoice::where('invoice_number', 'INV0001')->firstOrFail();
        $this->actingAs($user)->get(route('invoices.exportPdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guests_cannot_export(): void
    {
        $partner = $this->partnerWithHistory();

        $this->get(route('partners.exportPdf', $partner))->assertRedirect('/login');
    }
}
