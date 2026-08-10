<?php

return [
    'pending_expiration_hours' => (int) env('BOOKING_PENDING_EXPIRATION_HOURS', 24),
    'payment_due_hours' => (int) env('BOOKING_PAYMENT_DUE_HOURS', 24),
    'payment_reminder_hours' => (int) env('BOOKING_PAYMENT_REMINDER_HOURS', 6),
    'no_show_grace_hours' => (int) env('BOOKING_NO_SHOW_GRACE_HOURS', 30),
    'check_in_reminder_days' => (int) env('BOOKING_CHECK_IN_REMINDER_DAYS', 1),
];
