<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Game::create([
            'name' => 'Minedrop',
            'description' => 'Minedrop is a game that allows you to mine for diamonds.',
            'image' => 'minedrop.png',
            'link' => 'https://minedrop.com',
        ]);
    }
}
