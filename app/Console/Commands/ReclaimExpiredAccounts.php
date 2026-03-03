<?php

namespace App\Console\Commands;

use App\Services\AccountAllocationService;
use Illuminate\Console\Command;

class ReclaimExpiredAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:reclaim';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reclaim accounts from expired rental orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting account reclaim process...');
        
        $count = AccountAllocationService::reclaimExpiredAccounts();
        
        $this->info("Reclaimed {$count} expired accounts.");
        
        return Command::SUCCESS;
    }
}
