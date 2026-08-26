<?php

return [
    'extra_bedding_fee_per_night' => env('HOTEL_EXTRA_BEDDING_FEE_PER_NIGHT', 500),
    'service_fee_rate' => env('HOTEL_SERVICE_FEE_RATE', 0.08),
    'local_tax_rate' => env('HOTEL_LOCAL_TAX_RATE', 0.05),
    'vat_rate' => env('HOTEL_VAT_RATE', 0.12),
];
