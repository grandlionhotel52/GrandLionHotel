<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesReportCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_summarizes_paid_sales_and_filters_by_method(): void
    {
        $this->travelTo('2026-09-08 12:00:00');

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();

        $breakfastPayment = $this->createPayment($staff, 1000, Payment::METHOD_CASH, 'paid', '2026-09-05 10:00:00');
        $breakfastPayment->booking->guestDetail()->update(['meal_plan' => 'breakfast_included']);
        $this->createPayment($staff, 2000, Payment::METHOD_CASH, 'paid', '2026-09-06 10:00:00');
        $this->createPayment($staff, 3000, Payment::METHOD_GCASH, 'paid', '2026-09-06 11:00:00');

        $response = $this->actingAs($admin, 'admin')->get(route('admin.sales-report', [
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]));

        $response->assertOk()
            ->assertViewHas('summary', function (array $summary): bool {
                return $summary['gross_revenue'] === 6000.0
                    && $summary['room_sales'] === 5000.0
                    && $summary['food_sales'] === 1000.0
                    && $summary['total_revenue'] === 6000.0
                    && $summary['paid_bookings'] === 3
                    && $summary['average_sale'] === 2000.0;
            })
            ->assertViewHas('dailySales', function ($rows): bool {
                $salesDay = $rows->firstWhere('date', '2026-09-06');

                return $salesDay !== null
                    && $salesDay->gross_revenue === 5000.0
                    && $salesDay->revenue === 5000.0;
            });

        $this->actingAs($admin, 'admin')->get(route('admin.sales-report', [
            'from' => '2026-09-01',
            'to' => '2026-09-08',
            'method' => Payment::METHOD_CASH,
        ]))->assertOk()->assertViewHas('summary', function (array $summary): bool {
            return $summary['gross_revenue'] === 3000.0
                && $summary['room_sales'] === 2000.0
                && $summary['food_sales'] === 1000.0
                && $summary['total_revenue'] === 3000.0
                && $summary['paid_bookings'] === 2
                && $summary['average_sale'] === 1500.0;
        });

        $this->travelBack();
    }

    public function test_sales_report_separates_regular_and_vat_exempt_tax_components(): void
    {
        $this->travelTo('2026-09-08 12:00:00');

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();

        // Regular: 1,000 net sales + 120 VAT + 50 local tax.
        $this->createPayment($staff, 1170, Payment::METHOD_CASH, 'paid', '2026-09-08 09:00:00');

        // PWD/Senior example: 900 VAT-exempt base - 180 discount
        // = 720 net sales + 36 local tax.
        $pwdPayment = $this->createPayment($staff, 756, Payment::METHOD_CASH, 'paid', '2026-09-08 10:00:00');
        $pwdPayment->update([
            'discount_amount' => 180,
        ]);
        $pwdPayment->booking->discount()->create(['discount_type' => 'pwd']);

        $this->actingAs($admin, 'admin')->get(route('admin.sales-report', [
            'from' => '2026-09-08',
            'to' => '2026-09-08',
        ]))->assertOk()->assertViewHas('summary', function (array $summary): bool {
            return $summary['gross_revenue'] === 1926.0
                && $summary['gross_sales_before_discount'] === 2128.0
                && $summary['net_sales_excluding_vat'] === 1720.0
                && $summary['vat_exempt_sales'] === 900.0
                && $summary['vat_total'] === 120.0
                && $summary['local_tax_total'] === 86.0
                && $summary['total_discount'] === 180.0;
        });

        $this->travelBack();
    }

    public function test_admin_can_export_the_filtered_sales_report_for_excel(): void
    {
        $this->travelTo('2026-09-08 12:00:00');

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create(['name' => 'Front Desk']);
        $this->createPayment($staff, 1500, Payment::METHOD_CASH, 'paid', '2026-09-08 09:00:00');
        $this->createPayment($staff, 2500, Payment::METHOD_GCASH, 'paid', '2026-09-08 10:00:00');

        $response = $this->actingAs($admin, 'admin')->get(route('admin.sales-report.export', [
            'from' => '2026-09-08',
            'to' => '2026-09-08',
            'method' => Payment::METHOD_CASH,
        ]));

        $response->assertOk()
            ->assertDownload('sales-report-2026-09-08-to-2026-09-08.csv');

        $content = $response->streamedContent();
        $this->assertStringContainsString('The Grand Lion Hotel - Sales Report', $content);
        $this->assertStringContainsString('"Payment Method",Cash', $content);
        $this->assertStringContainsString('"Total Sales",1500', $content);
        $this->assertStringContainsString('DAILY SALES', $content);
        $this->assertStringContainsString('STAFF PERFORMANCE', $content);
        $this->assertStringNotContainsString('GCash via PayMongo', $content);

        $this->travelBack();
    }

    public function test_each_paid_sale_links_to_an_inline_printable_receipt(): void
    {
        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();
        $payment = $this->createPayment($staff, 1500, Payment::METHOD_CASH, 'paid', now()->toDateTimeString());

        $report = $this->actingAs($admin, 'admin')->get(route('admin.sales-report', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $report->assertOk()
            ->assertSee(route('admin.sales-report.metric', [
                'metric' => 'total-sales',
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
                'method' => 'all',
            ]))
            ->assertSee(route('admin.sales-report.receipt', $payment), false)
            ->assertSee('View / Print');

        $breakdown = $this->actingAs($admin, 'admin')->get(route('admin.sales-report.metric', [
            'metric' => 'total-sales',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $breakdown->assertOk()
            ->assertViewHas('metricTotal', 1500.0)
            ->assertSee('Total Sales Breakdown')
            ->assertSee('Print Landscape')
            ->assertSee(route('admin.sales-report.receipt', $payment), false);

        $receipt = $this->actingAs($admin, 'admin')->get(route('admin.sales-report.receipt', $payment));

        $receipt->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=sale-receipt-'.$payment->id.'.pdf');

        $this->assertNotNull($payment->fresh()->transaction_reference);
    }

    private function createPayment(Staff $staff, float $amount, string $method, string $status, string $paidAt): Payment
    {
        $booking = Booking::factory()->create([
            'staff_id' => $staff->id,
            'status' => 'confirmed',
        ]);

        $booking->payment()->update([
            'amount' => $amount,
            'method' => $method,
            'status' => $status,
            'paid_at' => $paidAt,
        ]);

        return $booking->payment()->firstOrFail();
    }

}
