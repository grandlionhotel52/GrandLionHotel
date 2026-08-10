<?php

namespace App\Support;

final class Money
{
    public static function display(float|int|string|null $amount): string
    {
        $formatted = number_format((float) $amount, 2);

        return str_ends_with($formatted, '.00')
            ? substr($formatted, 0, -3)
            : $formatted;
    }

    public static function exact(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2);
    }
}
