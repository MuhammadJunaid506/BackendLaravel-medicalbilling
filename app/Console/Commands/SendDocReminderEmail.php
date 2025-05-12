<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DocumentsController;

class SendDocReminderEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminder to users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $docObj = new DocumentsController();

        $docObj->sendDocumentExpiryEmail();
        
        $this->info('Reminder email sent successfully!');
        //return 0;
    }
}
