<?php

namespace Database\Seeders;

use App\Models\Governorate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    /**
     * Seed governorates mapped to shipping zones.
     */
    public function run(): void
    {
        $governorates = [
            ['zone' => 'cairo_giza', 'name' => 'Cairo', 'name_ar' => 'القاهرة'],
            ['zone' => 'cairo_giza', 'name' => 'Giza', 'name_ar' => 'الجيزة'],
            ['zone' => 'alexandria_coast', 'name' => 'Alexandria', 'name_ar' => 'الإسكندرية'],
            ['zone' => 'alexandria_coast', 'name' => 'Beheira', 'name_ar' => 'البحيرة'],
            ['zone' => 'alexandria_coast', 'name' => 'Matrouh', 'name_ar' => 'مطروح'],
            ['zone' => 'delta', 'name' => 'Qalyubia', 'name_ar' => 'القليوبية'],
            ['zone' => 'delta', 'name' => 'Dakahlia', 'name_ar' => 'الدقهلية'],
            ['zone' => 'delta', 'name' => 'Sharqia', 'name_ar' => 'الشرقية'],
            ['zone' => 'delta', 'name' => 'Gharbia', 'name_ar' => 'الغربية'],
            ['zone' => 'delta', 'name' => 'Menofia', 'name_ar' => 'المنوفية'],
            ['zone' => 'delta', 'name' => 'Kafr El Sheikh', 'name_ar' => 'كفر الشيخ'],
            ['zone' => 'delta', 'name' => 'Damietta', 'name_ar' => 'دمياط'],
            ['zone' => 'upper_egypt', 'name' => 'Fayoum', 'name_ar' => 'الفيوم'],
            ['zone' => 'upper_egypt', 'name' => 'Beni Suef', 'name_ar' => 'بني سويف'],
            ['zone' => 'upper_egypt', 'name' => 'Minya', 'name_ar' => 'المنيا'],
            ['zone' => 'upper_egypt', 'name' => 'Assiut', 'name_ar' => 'أسيوط'],
            ['zone' => 'upper_egypt', 'name' => 'Sohag', 'name_ar' => 'سوهاج'],
            ['zone' => 'upper_egypt', 'name' => 'Qena', 'name_ar' => 'قنا'],
            ['zone' => 'upper_egypt', 'name' => 'Luxor', 'name_ar' => 'الأقصر'],
            ['zone' => 'upper_egypt', 'name' => 'Aswan', 'name_ar' => 'أسوان'],
        ];

        foreach ($governorates as $governorate) {
            $zone = ShippingZone::where('code', $governorate['zone'])->first();

            if (! $zone) {
                continue;
            }

            Governorate::updateOrCreate(
                [
                    'shipping_zone_id' => $zone->id,
                    'name' => $governorate['name'],
                ],
                [
                    'name_ar' => $governorate['name_ar'],
                    'is_active' => true,
                ],
            );
        }
    }
}
