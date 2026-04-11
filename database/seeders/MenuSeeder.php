<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuAlias;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Seed dummy menu data for voice-input testing.
     */
    public function run(): void
    {
        $menus = [
            [
                'name' => 'nasi goreng',
                'price' => 18000,
                'aliases' => ['nasgor', 'nasi goring', 'nasi gorenk'],
            ],
            [
                'name' => 'mie goreng',
                'price' => 17000,
                'aliases' => ['migor', 'mie goring', 'mi goreng'],
            ],
            [
                'name' => 'ayam geprek',
                'price' => 20000,
                'aliases' => ['geprek', 'ayam geprak'],
            ],
            [
                'name' => 'teh',
                'price' => 6000,
                'aliases' => ['teh anget', 'teh hangat', 'teh panas'],
            ],
            [
                'name' => 'teh manis dingin',
                'price' => 8000,
                'aliases' => ['es teh', 'es teh manis', 'teh es manis'],
            ],
            [
                'name' => 'air mineral',
                'price' => 5000,
                'aliases' => ['air putih', 'mineral'],
            ],
        ];

        foreach ($menus as $item) {
            $menu = Menu::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'is_active' => true,
                ],
            );

            foreach ($item['aliases'] as $alias) {
                MenuAlias::query()->updateOrCreate(
                    ['normalized_alias' => Str::lower(trim($alias))],
                    [
                        'menu_id' => $menu->id,
                        'alias' => $alias,
                    ],
                );
            }
        }
    }
}
