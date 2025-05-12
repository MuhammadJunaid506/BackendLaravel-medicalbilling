<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckServicePlanExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:plan-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for expired plans and performs actions.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now();

        return "command:check-plan-expiration working fine.";
    }
}
