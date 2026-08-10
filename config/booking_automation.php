<?php

return [
    'pending_expiration_hours' => (int) env('BOOKING_PENDING_EXPIRATION_HOURS', 24),
    'payment_due_hours' => (int) env('BOOKING_PAYMENT_DUE_HOURS', 24),
    'check_in_reminder_days' => (int) env('BOOKING_CHECK_IN_REMINDER_DAYS', 1),
];
