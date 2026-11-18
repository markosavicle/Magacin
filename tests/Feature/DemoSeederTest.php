<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Partner;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_fictional_and_consistent(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(12, Partner::count());
        $this->assertGreaterThan(50, Invoice::count());

        foreach (Partner::all() as $partner) {
            $pib = (int) $partner->vat_number;
            $this->assertTrue($pib >= DemoSeeder::FIRST_PIB && $pib < DemoSeeder::FIRST_PIB + 99, "PIB {$pib} outside the demo range");
            $this->assertStringEndsWith('@primer.example', $partner->email);
        }

        $this->artisan('partners:verify-balances')->assertExitCode(0);
    }

    public function test_it_runs_only_once(): void
    {
        $this->seed(DemoSeeder::class);
        $invoices = Invoice::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame(12, Partner::count());
        $this->assertSame($invoices, Invoice::count());
    }

    public function test_customers_owe_us_and_we_owe_suppliers(): void
    {
        $this->seed(DemoSeeder::class);

        foreach (Partner::all() as $partner) {
            $balance = $partner->balance();
            $partner->type === 'kupac'
                ? $this->assertGreaterThanOrEqual(0, $balance, $partner->name)
                : $this->assertLessThanOrEqual(0, $balance, $partner->name);
        }
    }
}
