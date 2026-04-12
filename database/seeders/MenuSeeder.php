<?php

namespace Database\Seeders;

use App\Models\Category;
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
        $categories = [
            'Makanan Berat' => [
                'description' => 'Menu utama untuk makan kenyang.',
                'is_active' => true,
            ],
            'Minuman' => [
                'description' => 'Pilihan minuman panas dan dingin.',
                'is_active' => true,
            ],
            'Camilan' => [
                'description' => 'Menu ringan untuk teman ngobrol.',
                'is_active' => true,
            ],
            'Menu Paket' => [
                'description' => 'Paket hemat kombinasi makanan dan minuman.',
                'is_active' => true,
            ],
        ];

        $categoryIds = [];

        foreach ($categories as $name => $meta) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $meta['description'],
                    'is_active' => $meta['is_active'],
                ],
            );

            $categoryIds[$name] = $category->id;
        }

        $menus = [
            [
                'name' => 'nasi goreng',
                'price' => 18000,
                'description' => 'Nasi goreng klasik dengan telur dan kerupuk.',
                'category' => 'Makanan Berat',
                'aliases' => ['nasgor', 'nasi goring', 'nasi gorenk'],
            ],
            [
                'name' => 'mie goreng',
                'price' => 17000,
                'description' => 'Mie goreng gurih dengan topping ayam suwir.',
                'category' => 'Makanan Berat',
                'aliases' => ['migor', 'mie goring', 'mi goreng'],
            ],
            [
                'name' => 'ayam geprek',
                'price' => 20000,
                'description' => 'Ayam crispy geprek sambal bawang level sedang.',
                'category' => 'Makanan Berat',
                'aliases' => ['geprek', 'ayam geprak'],
            ],
            [
                'name' => 'teh',
                'price' => 6000,
                'description' => 'Teh hangat manis, cocok untuk semua cuaca.',
                'category' => 'Minuman',
                'aliases' => ['teh anget', 'teh hangat', 'teh panas'],
            ],
            [
                'name' => 'teh manis dingin',
                'price' => 8000,
                'description' => 'Es teh manis segar dengan es batu melimpah.',
                'category' => 'Minuman',
                'aliases' => ['es teh', 'es teh manis', 'teh es manis'],
            ],
            [
                'name' => 'air mineral',
                'price' => 5000,
                'description' => 'Air mineral botol dingin untuk pendamping makan.',
                'category' => 'Minuman',
                'aliases' => ['air putih', 'mineral'],
            ],
            [
                'name' => 'kentang goreng',
                'price' => 12000,
                'description' => 'Kentang goreng renyah dengan bumbu ringan.',
                'category' => 'Camilan',
                'aliases' => ['french fries', 'kentang', 'kentang crispy'],
            ],
            [
                'name' => 'roti bakar coklat',
                'price' => 14000,
                'description' => 'Roti bakar hangat isi coklat lumer.',
                'category' => 'Camilan',
                'aliases' => ['robak coklat', 'toast coklat', 'roti coklat'],
            ],
            [
                'name' => 'paket hemat nasgor',
                'price' => 25000,
                'description' => 'Nasi goreng + es teh manis dengan harga hemat.',
                'category' => 'Menu Paket',
                'aliases' => ['paket nasgor', 'paket nasi goreng', 'paket 1'],
            ],
            [
                'name' => 'paket geprek komplit',
                'price' => 28000,
                'description' => 'Ayam geprek + nasi + teh hangat.',
                'category' => 'Menu Paket',
                'aliases' => ['paket geprek', 'paket ayam', 'paket 2'],
            ],
            [
                'name' => 'paket mie duo',
                'price' => 30000,
                'description' => 'Mie goreng + kentang goreng + air mineral.',
                'category' => 'Menu Paket',
                'aliases' => ['paket mie', 'paket migor', 'paket 3'],
            ],
            [
                'name' => 'paket nongkrong',
                'price' => 32000,
                'description' => 'Roti bakar coklat + teh manis dingin + kentang.',
                'category' => 'Menu Paket',
                'aliases' => ['paket camilan', 'paket roti', 'paket santai'],
            ],
        ];

        foreach ($menus as $item) {
            $menu = Menu::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'description' => $item['description'],
                    'category_id' => $categoryIds[$item['category']] ?? null,
                    'is_active' => true,
                ],
            );

            foreach ($item['aliases'] as $alias) {
                $normalizedAlias = Str::of($alias)->lower()->squish()->toString();

                MenuAlias::query()->updateOrCreate(
                    ['normalized_alias' => $normalizedAlias],
                    [
                        'menu_id' => $menu->id,
                        'alias' => $alias,
                        'normalized_alias' => $normalizedAlias,
                    ],
                );
            }
        }
    }
}
