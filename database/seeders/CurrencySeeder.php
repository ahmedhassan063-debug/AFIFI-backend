<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seed supported currencies.
     */
    public function run(): void
    {
        Currency::updateOrCreate(
            ['code' => 'EGP'],
            [
                'name' => 'Egyptian Pound',
                'symbol' => 'EGP',
                'decimal_places' => 2,
                'exchange_rate' => 1.00000000,
                'is_default' => true,
                'is_active' => true,
            ],
        );
    }
}
