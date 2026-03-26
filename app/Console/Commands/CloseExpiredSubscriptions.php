<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class CloseExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cheq subscriptions:close-expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::where('status', 'active')
            ->decrement("number_date", 1);
        $count = User::where("number_day", "<=", 0)
            ->where("status", "!=", "expired")
            ->update(["status" => "expired"]);

       
        User::where("number_day", ">", 0)
            ->where("status", "expired")
            ->update(["status" => "active"]);
    }
}
