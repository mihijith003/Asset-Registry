<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Financial year start month
    |--------------------------------------------------------------------------
    |
    | Month (1-12) in which the financial year starts. 4 = April, so a financial year runs
    | 1 April - 31 March and is labelled like "2025/2026". Use 1 for calendar-year financial years
    | (labelled "2025").
    |
    */
    'financial_year_start_month' => (int) env('FINANCIAL_YEAR_START_MONTH', 4),

    /*
    |--------------------------------------------------------------------------
    | How many financial years the "Financial Year" dropdowns list
    |--------------------------------------------------------------------------
    */
    'years_back' => 10,
    'years_forward' => 1,
];
