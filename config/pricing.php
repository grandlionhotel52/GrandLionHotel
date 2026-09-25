<?php

return [
    'extra_bedding_fee_per_night' => env('HOTEL_EXTRA_BEDDING_FEE_PER_NIGHT', 500),
    'max_extra_bedding_per_booking' => env('HOTEL_MAX_EXTRA_BEDDING_PER_BOOKING', 5),
    'breakfast_fee' => env('HOTEL_BREAKFAST_FEE', 1200),
    'service_fee_rate' => env('HOTEL_SERVICE_FEE_RATE', 0.08),
    'local_tax_rate' => env('HOTEL_LOCAL_TAX_RATE', 0.05),
    'local_tax_applies_to_vat_exempt_sales' => env('HOTEL_LOCAL_TAX_APPLIES_TO_VAT_EXEMPT_SALES', true),
    'vat_rate' => env('HOTEL_VAT_RATE', 0.12),
];
