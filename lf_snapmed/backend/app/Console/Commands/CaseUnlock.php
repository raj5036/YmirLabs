<?php

namespace App\Console\Commands;

use Log;

use App\Examination;

use Carbon\Carbon;

use Illuminate\Console\Command;

class CaseUnlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'case:unlock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlock cases that have been locked by a physician for more than 30 minutes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get date time that was 30min ago.
        $thirtyMinutesAgo = Carbon::now(env('APP_TIMEZONE'))->subMinutes(30);

        // Find cases that are more than 30 minutes old
        $coldCaseQuery = Examination::where('complete', '=', true)
            ->where('diagnosed', '=', false)
            ->whereNotNull('charged')
            ->whereNotNull('stripe')
            ->whereNotNull('locked_by')
            ->where('updated_at', '<', $thirtyMinutesAgo);
        // Are there any cases that are older than 30 minutes?
        if ($coldCaseQuery->exists()) {
            // Yes, then unlock the cases so that other doctors can work them.
            $coldCases = $coldCaseQuery->get();
            foreach ($coldCases as $case) {
                Log::info('Unlocking case: ' . $case->uuid . ' - which was locked by: ' . $case->locked_by);
                $case->locked_by = null;
                $case->save();
            }
        }
    }
}
