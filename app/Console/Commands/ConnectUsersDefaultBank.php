<?php

namespace App\Console\Commands;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Console\Command;

class ConnectUsersDefaultBank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connect:users-default-bank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultBank = Bank::where('is_default', true)->first();
        $users = User::where('bank_id', null)->get();
        foreach ($users as $user) {
            $user->bank_id = $defaultBank->id;
            $user->save();
        }
        $this->info('Users connected to default bank');
    }
}
