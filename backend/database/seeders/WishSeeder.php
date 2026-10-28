<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Wish;
use Illuminate\Database\Seeder;

class WishSeeder extends Seeder
{
    public function run(): void
    {
        $wishes = [
            [
                'title' => 'Годинник TOUS D-Logo Mirror 3000131700',
                'brand' => 'TOUS',
                'product_url' => 'https://www.tous.com/ua-uk/godinniki/3000131700.html',
                'price' => 10989.00,
                'original_price' => null,
            ],
            [
                'title' => 'Намисто із логотипом Pandora',
                'brand' => 'Pandora',
                'product_url' => 'https://e-pandora.ua/product/namista_i_kuloni_590742hv',
                'price' => 7995.00,
                'original_price' => null,
            ],
            [
                'title' => 'Браслет «Символи домів Старків та Таргарієнів» Гра Престолів',
                'brand' => 'Pandora',
                'product_url' => 'https://e-pandora.ua/product/braslet_simvoli_domiv_starkiv_ta_targarijeniv_gra_prestoliv',
                'price' => 4056.00,
                'original_price' => 5795.00,
            ],
            [
                'title' => 'Медальйон «Справжня чеснота — це процвітання»',
                'brand' => 'Pandora',
                'product_url' => 'https://e-pandora.ua/product/namistina_spravznia_cesnota_ce_procvitannia',
                'price' => 3995.00,
                'original_price' => null,
            ],
            [
                'title' => 'Намистина «Бджілка» Pandora ME',
                'brand' => 'Pandora',
                'product_url' => 'https://e-pandora.ua/product/namistina_bdzilka',
                'price' => 3295.00,
                'original_price' => null,
            ],
        ];

        foreach ($wishes as $wish) {
            Wish::firstOrCreate(
                ['product_url_hash' => hash('sha256', $wish['product_url'])],
                array_merge($wish, [
                    'currency' => 'UAH',
                    'is_purchased' => false,
                ])
            );
        }
    }
}
