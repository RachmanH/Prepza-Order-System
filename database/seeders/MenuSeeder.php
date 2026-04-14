<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuAlias;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Makanan Berat' => [
                'description' => 'Menu utama untuk makan kenyang.',
                'is_active'   => true,
            ],
            'Minuman' => [
                'description' => 'Pilihan minuman panas dan dingin.',
                'is_active'   => true,
            ],
            'Camilan' => [
                'description' => 'Menu ringan untuk teman ngobrol.',
                'is_active'   => true,
            ],
            'Menu Paket' => [
                'description' => 'Paket hemat kombinasi makanan dan minuman.',
                'is_active'   => true,
            ],
        ];

        $categoryIds = [];

        foreach ($categories as $name => $meta) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name'        => $name,
                    'description' => $meta['description'],
                    'is_active'   => $meta['is_active'],
                ],
            );

            $categoryIds[$name] = $category->id;
        }

        $menus = [
            // ── Makanan Berat ──────────────────────────────────────────────
            [
                'name'        => 'nasi goreng',
                'price'       => 18000,
                'description' => 'Nasi goreng klasik dengan telur ceplok, kerupuk, dan acar timun.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&q=80',
                'aliases'     => ['nasgor', 'nasi goring', 'nasi gorenk'],
            ],
            [
                'name'        => 'mie goreng',
                'price'       => 17000,
                'description' => 'Mie goreng gurih dengan topping ayam suwir dan sayuran segar.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=400&q=80',
                'aliases'     => ['migor', 'mie goring', 'mi goreng'],
            ],
            [
                'name'        => 'ayam geprek',
                'price'       => 20000,
                'description' => 'Ayam crispy geprek sambal bawang level sedang, disajikan dengan nasi putih.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=400&q=80',
                'aliases'     => ['geprek', 'ayam geprak'],
            ],
            [
                'name'        => 'nasi uduk',
                'price'       => 16000,
                'description' => 'Nasi uduk gurih santan lengkap dengan tempe orek, bihun, dan kerupuk.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&q=80',
                'aliases'     => ['nasi uduk komplit', 'uduk'],
            ],
            [
                'name'        => 'soto ayam',
                'price'       => 18000,
                'description' => 'Soto ayam kuah bening dengan suwiran ayam, tauge, dan bihun.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&q=80',
                'aliases'     => ['soto', 'soto ayam bening'],
            ],
            [
                'name'        => 'nasi bakar',
                'price'       => 22000,
                'description' => 'Nasi bakar isi ayam suwir bumbu kemangi, dibungkus daun pisang.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1574484284002-952d92456975?w=400&q=80',
                'aliases'     => ['nasi bakar ayam', 'nasibakar'],
            ],
            [
                'name'        => 'ayam bakar',
                'price'       => 25000,
                'description' => 'Ayam bakar bumbu kecap manis dengan lalapan dan sambal terasi.',
                'category'    => 'Makanan Berat',
                'image_url'   => 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=400&q=80',
                'aliases'     => ['bakar ayam', 'ayam bakar kecap'],
            ],

            // ── Minuman ────────────────────────────────────────────────────
            [
                'name'        => 'teh',
                'price'       => 6000,
                'description' => 'Teh hangat manis, cocok untuk semua cuaca.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80',
                'aliases'     => ['teh anget', 'teh hangat', 'teh panas'],
            ],
            [
                'name'        => 'teh manis dingin',
                'price'       => 8000,
                'description' => 'Es teh manis segar dengan es batu melimpah.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1499638673689-79a0b5115d87?w=400&q=80',
                'aliases'     => ['es teh', 'es teh manis', 'teh es manis'],
            ],
            [
                'name'        => 'air mineral',
                'price'       => 5000,
                'description' => 'Air mineral botol dingin untuk pendamping makan.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=400&q=80',
                'aliases'     => ['air putih', 'mineral'],
            ],
            [
                'name'        => 'kopi hitam',
                'price'       => 8000,
                'description' => 'Kopi hitam tubruk khas warung, pahit dan harum.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&q=80',
                'aliases'     => ['kopi', 'kopi tubruk', 'kopi item'],
            ],
            [
                'name'        => 'kopi susu',
                'price'       => 12000,
                'description' => 'Kopi susu creamy dengan gula aren, disajikan dingin.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&q=80',
                'aliases'     => ['kopi susu gula aren', 'kopsu', 'kopi aren'],
            ],
            [
                'name'        => 'jus jeruk',
                'price'       => 12000,
                'description' => 'Jus jeruk segar diperas langsung, manis alami.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=400&q=80',
                'aliases'     => ['jeruk peras', 'jus jeruk segar', 'orange juice'],
            ],
            [
                'name'        => 'es jeruk',
                'price'       => 10000,
                'description' => 'Es jeruk peras dingin menyegarkan.',
                'category'    => 'Minuman',
                'image_url'   => 'https://images.unsplash.com/photo-1534353436294-0dbd4bdac845?w=400&q=80',
                'aliases'     => ['jeruk dingin', 'es jeruk peras'],
            ],

            // ── Camilan ────────────────────────────────────────────────────
            [
                'name'        => 'kentang goreng',
                'price'       => 12000,
                'description' => 'Kentang goreng renyah dengan bumbu balado ringan.',
                'category'    => 'Camilan',
                'image_url'   => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=400&q=80',
                'aliases'     => ['french fries', 'kentang', 'kentang crispy'],
            ],
            [
                'name'        => 'roti bakar coklat',
                'price'       => 14000,
                'description' => 'Roti bakar hangat isi coklat keju lumer.',
                'category'    => 'Camilan',
                'image_url'   => 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?w=400&q=80',
                'aliases'     => ['robak coklat', 'toast coklat', 'roti coklat'],
            ],
            [
                'name'        => 'pisang goreng',
                'price'       => 10000,
                'description' => 'Pisang goreng crispy dengan taburan gula halus dan keju.',
                'category'    => 'Camilan',
                'image_url'   => 'https://images.unsplash.com/photo-1528975604071-b4dc52a2d18c?w=400&q=80',
                'aliases'     => ['pisgor', 'banana fritter', 'pisang goreng crispy'],
            ],
            [
                'name'        => 'singkong goreng',
                'price'       => 9000,
                'description' => 'Singkong goreng empuk dalam, renyah luar, cocok dengan sambal.',
                'category'    => 'Camilan',
                'image_url'   => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400&q=80',
                'aliases'     => ['singkong', 'ubi goreng', 'cassava goreng'],
            ],
            [
                'name'        => 'tempe mendoan',
                'price'       => 8000,
                'description' => 'Tempe mendoan tipis berbumbu, digoreng setengah matang, gurih.',
                'category'    => 'Camilan',
                'image_url'   => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=400&q=80',
                'aliases'     => ['mendoan', 'tempe goreng', 'tempe'],
            ],

            // ── Menu Paket ─────────────────────────────────────────────────
            [
                'name'        => 'paket hemat nasgor',
                'price'       => 25000,
                'description' => 'Nasi goreng + es teh manis dengan harga hemat.',
                'category'    => 'Menu Paket',
                'image_url'   => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&q=80',
                'aliases'     => ['paket nasgor', 'paket nasi goreng', 'paket 1'],
            ],
            [
                'name'        => 'paket geprek komplit',
                'price'       => 28000,
                'description' => 'Ayam geprek + nasi + teh hangat.',
                'category'    => 'Menu Paket',
                'image_url'   => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=400&q=80',
                'aliases'     => ['paket geprek', 'paket ayam', 'paket 2'],
            ],
            [
                'name'        => 'paket mie duo',
                'price'       => 30000,
                'description' => 'Mie goreng + kentang goreng + air mineral.',
                'category'    => 'Menu Paket',
                'image_url'   => 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=400&q=80',
                'aliases'     => ['paket mie', 'paket migor', 'paket 3'],
            ],
            [
                'name'        => 'paket nongkrong',
                'price'       => 32000,
                'description' => 'Roti bakar coklat + kopi susu + kentang goreng.',
                'category'    => 'Menu Paket',
                'image_url'   => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&q=80',
                'aliases'     => ['paket camilan', 'paket roti', 'paket santai'],
            ],
            [
                'name'        => 'paket soto komplit',
                'price'       => 26000,
                'description' => 'Soto ayam + nasi putih + es teh manis.',
                'category'    => 'Menu Paket',
                'image_url'   => 'https://images.unsplash.com/photo-1547592180-85f173990554?w=400&q=80',
                'aliases'     => ['paket soto', 'paket soto ayam', 'paket 4'],
            ],
            [
                'name'        => 'paket bakar spesial',
                'price'       => 35000,
                'description' => 'Ayam bakar + nasi uduk + es jeruk.',
                'category'    => 'Menu Paket',
                'image_url'   => 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=400&q=80',
                'aliases'     => ['paket ayam bakar', 'paket spesial', 'paket 5'],
            ],
        ];

        foreach ($menus as $item) {
            $menu = Menu::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name'        => $item['name'],
                    'price'       => $item['price'],
                    'description' => $item['description'],
                    'category_id' => $categoryIds[$item['category']] ?? null,
                    'image_url'   => $item['image_url'] ?? null,
                    'is_active'   => true,
                ],
            );

            foreach ($item['aliases'] as $alias) {
                $normalizedAlias = Str::of($alias)->lower()->squish()->toString();

                MenuAlias::query()->updateOrCreate(
                    ['normalized_alias' => $normalizedAlias],
                    [
                        'menu_id'          => $menu->id,
                        'alias'            => $alias,
                        'normalized_alias' => $normalizedAlias,
                    ],
                );
            }
        }
    }
}
