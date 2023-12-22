<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Examination;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class StbExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stb:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Storebrand users';

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
        $examinations = Examination::where('complete', '=', true)->where('payment_type', '=', Examination::PAYMENT_TYPE_PARTNER )
                                   ->where(function($query) {
                                       $query->where('diagnosed', '=', true)->orWhere('category', '=', 'video');
                                   })
                                    ->with(['client' => function ($query) {
                                        $query->where('partner', '=', 'storebrand');
                                    }])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        foreach($examinations as $examination) {
            $description_private = '';
            $description_private_count = 0;
            if($examination->diagnoses) {
                foreach($examination->diagnoses as $diagnosis) {
                    $description_private_count++;
                    if($diagnosis->description_private) {
                        $description_private .= '#' . $description_private_count . ': '. $diagnosis->description_private . ' ';
                    }
                }
            }

            $partnerClaimNumber = '';
            if($examination->partnerClaim) {
                $partnerClaimNumber = $examination->partnerClaim->claimnumber;
            }

            $this->info(
                $examination->uuid . ',' . $examination->created_at . ',' .
                $examination->client->firstname . ' ' . $examination->client->lastname . ',' .
                $examination->client->partner_policynumber . ',' .
                $partnerClaimNumber,
                Crypt::decryptString($examination->client->ssn) . ',' .
                ($examination->category === 'video' ? 'Video-konsultasjon' : 'Bilde-konsultasjon') . ',' . $description_private
            );
        }
    }
}
