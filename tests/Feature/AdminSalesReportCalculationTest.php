<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesReportCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_keeps_paid_sales_and_reconciles_processed_refunds(): void
    {
        $this->travelTo('2026-09-08 12:00:00');

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();

        $pendingRefundPayment = $this->createPayment($staff, 1000, Payment::METHOD_CASH, 'refund_pending', '2026-09-05 10:00:00');
        $cashRefundedPayment = $this->createPayment($staff, 2000, Payment::METHOD_CASH, 'refunded', '2026-09-06 10:00:00');
        $gcashRefundedPayment = $this->createPayment($staff, 3000, Payment::METHOD_GCASH, 'refunded', '2026-09-06 11:00:00');

        $this->createProcessedRefund($cashRefundedPayment, 500, '2026-09-07 09:00:00');
        $this->createProcessedRefund($gcashRefundedPayment, 700, '2026-09-07 10:00:00');

        $response = $this->actingAs($admin, 'admin')->get(route('admin.sales-report', [
            'from' => '2026-09-01',
            'to' => '2026-09-08',
        ]));

        $response->assertOk()
            ->assertViewHas('summary', function (array $summary): bool {
                return $summary['gross_revenue'] === 6000.0
                    && $summary['refunded_total'] === 1200.0
                    && $summary['total_revenue'] === 4800.0
                    && $summary['paid_bookings'] === 3
                    && $summary['average_sale'] === 2000.0;
            })
            ->assertViewHas('dailySales', function ($rows): bool {
                $refundDay = $rows->firstWhere('date', '2026-09-07');

                return $refundDay !== null
                    && $refundDay->gross_revenue === 0.0
                    && $refundDay->refunded_total === 1200.0
                    && $refundDay->revenue === -1200.0;
            });

        // The method filter must not subtract refunds issued through another method.
        $this->actingAs($admin, 'admin')->get(route('admin.sales-report', [
            'from' => '2026-09-01',
            'to' => '2026-09-08',
            'method' => Payment::METHOD_CASH,
        ]))->assertOk()->assertViewHas('summary', function (array $summary): bool {
            return $summary['gross_revenue'] === 3000.0
                && $summary['refunded_total'] === 500.0
                && $summary['total_revenue'] === 2500.0
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

    private function createProcessedRefund(Payment $payment, float $amount, string $processedAt): void
    {
        RefundRequest::query()->create([
            'payment_id' => $payment->id,
            'reason' => 'Test refund',
            'status' => RefundRequest::STATUS_PROCESSED,
            'amount' => $amount,
            'refund_method' => $payment->method,
            'transaction_reference' => 'REF-'.$payment->id,
            'requested_at' => $processedAt,
            'approved_at' => $processedAt,
            'processed_at' => $processedAt,
        ]);
    }
}
