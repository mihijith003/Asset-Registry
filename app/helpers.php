<?php

/*
 * Global helpers, loaded through composer.json "autoload": {"files": ["app/helpers.php"]}.
 */

if (! function_exists('currency_symbol')) {
    /**
     * The currency symbol shown with every money figure (config/app.php 'currency_symbol' - the one place it is set).
     */
    function currency_symbol(): string
    {
        return (string) config('app.currency_symbol');
    }
}

if (! function_exists('money')) {
    /**
     * A money amount for display: "Rs. 13,391,999.71" - thousands separators, always two decimals, and a
     * non-breaking space after the symbol so it never wraps. Negative amounts in brackets, never a minus sign:
     * "Rs. (4,550,000.00)". Null / '' (not known) shows "-".
     */
    function money($amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        $amount = round((float) $amount, 2);
        $digits = number_format(abs($amount), 2);

        return currency_symbol() . "\u{00A0}" . ($amount < 0 ? "({$digits})" : $digits);
    }
}
