<?php

namespace App\Console\Commands;
use Log;
use Illuminate\Console\Command;
use App\Examination;
use App\Notification;
use Carbon\Carbon;
use \Mailjet\Resources;

class SendMailRetain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:retain-email {from_date?} {to_date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email to the users who have unfinished consultations';

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

        $from_date = $this->argument('from_date');
        $to_date = $this->argument('to_date');

        

        if($from_date && $to_date){
            $from_date = Carbon::createFromFormat('Y-m-d', $from_date);
            $to_date = Carbon::createFromFormat('Y-m-d', $to_date);

            if($to_date->greaterThan($from_date)){
                $minCheck = $from_date;
                $maxCheck = $to_date;
            }else {
                $this->error('from_date should be smaller than to_date');
                return false;
            }
        }else{
            $minCheck = Carbon::now(env('APP_TIMEZONE'))->endOfDay()->subDays(2);
            $maxCheck = Carbon::now(env('APP_TIMEZONE'))->endOfDay()->subDays(1);
        }

        $users = Examination::whereNull('locked_by')
            ->where('updated_at', '>', $minCheck)
            ->where('updated_at', '<', $maxCheck)
            ->with(array('client' => function ($query) {
                $query->select('uuid', 'region','email','firstname','lastname');
            }))->get()
            ->pluck('client')
            ->toArray();

        
        Log::info($users);
        Log::info($this->signature . ' - sending NPS emails');
        Log::info(print_r($users, TRUE));


        $this->info('sending retainer emails ...');        
        foreach ($users as $user) {            
            $variables = [
                "name" => $user['firstname']." ".$user['lastname']
            ];
            if($user['region']==='uk'){
                Log::info('<======== UK ========>');
                $response = $this->sendMailViaMailjet("support@snapmed.co", $user['email'], (int) env('MJ_USER_RETAIN_TEMPLATE'), $variables);
                if ($response->success()) {
                    Log::info('Retainer email sent to ' . $user['email']);
                }else{
                    Log::error('Retainer email failed for ' . $user['email']);
                    Log::error(print_r($response));
                }
            }
            // elseif($user['region']==='no'){
            //     Log::info('<======== NO ========>');
            //     $response = $this->sendMailViaMailjet("support@snapmed.co", $user['email'], (int) env('MJ_USER_RETAIN_TEMPLATE'), $variables);
            //     if ($response->success()) {
            //         Log::info('Retainer email sent to ' . $user['email']);
            //     }else{
            //         Log::error('Retainer email failed for ' . $user['email']);
            //         Log::error(print_r($response));
            //     }

            // }
            // elseif($user['region']==='se'){
            //     Log::info('<======== SE ========>');
            //     $response = $this->sendMailViaMailjet("support@snapmed.co", $user['email'], (int) env('MJ_USER_RETAIN_TEMPLATE'), $variables);
            //     if ($response->success()) {
            //         Log::info('Retainer email sent to ' . $user['email']);
            //     }else{
            //         Log::error('Retainer email failed for ' . $user['email']);
            //         Log::error(print_r($response));
            //     }

            // }
            
        }
        $this->info('sent retainer emails');   
        return true;
    }



        /**
     * Private helper function to get a new Mailjet http client
     *
     * @return \GuzzleHttp\Client
     */
    private function getMailJetClient()
    {
        $mj = new \Mailjet\Client(env('MJ_API_KEY'), env('MJ_SECRET_KEY'), true, ['version' => 'v3.1']);
        return $mj;
    }

    /**
     * Private helper function to send email via Mailjet
     *
     * @return \GuzzleHttp\Client
     */
    private function sendMailViaMailjet($from, $to, $templateID, $variables)
    {
        $client = $this->getMailJetClient();
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "$from",
                        "Name" => "Snapmed"
                    ],
                    'To' => [
                        [
                            'Email' => "$to"
                        ]
                    ],
                    'TemplateID' => $templateID,
                    'TemplateLanguage' => true,
                    "Variables" => $variables
                ]
            ]
        ];
        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response;
    }
}
