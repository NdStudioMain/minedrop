<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Games;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bank = Bank::first();
        $games = [
            [
                'id' => 1,
                'id_game' => 'minedrop',
                'description' => 'Minedrop is a game that allows you to mine for diamonds.',
                'image' => 'assets/img/minedrop.png',
                'url_slug' => 'minedrop',
                'bank_id' => $bank->id,
            ],
            [
                'id' => 2,
                'id_game' => 'mines',
                'description' => 'Mines is a game that allows you to mine for diamonds.',
                'image' => 'assets/img/mines.png',
                'url_slug' => 'mines',
                'bank_id' => $bank->id,
            ],
            [
                'id' => 3,
                'id_game' => 'dice',
                'description' => 'Dice is a game that allows you to roll a dice.',
                'image' => 'assets/img/dice.png',
                'url_slug' => 'dice',
                'bank_id' => $bank->id,
            ],
        ];
        foreach ($games as $game) {
            Games::create($game);
        }
    }
}
