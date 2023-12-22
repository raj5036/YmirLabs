<?php

namespace App\Console\Commands;
use Log;
use Illuminate\Console\Command;
use App\Examination;
use App\Notification;
use Carbon\Carbon;
use \Mailjet\Resources;

class SendNPSEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:nps-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send NPS emails to the users who have paid for the services 3 days ago';

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
        $minCheck = Carbon::now(env('APP_TIMEZONE'))->subDays(3);
        $maxCheck = Carbon::now(env('APP_TIMEZONE'))->subDays(2);

        // Check if there are any new cases created after the lastCheck.
        $users = Examination::where('complete', '=', true)
            ->where('created_at', '>', $minCheck)
            ->where('created_at', '<', $maxCheck)
            ->where(function ($query) {
                $query->orWhere(function ($orQuery) {
                    $orQuery->whereNotNull('charged');
                    $orQuery->whereNotNull('stripe');
                });
            })
            ->with(array('client' => function ($query) {
                $query->select('uuid', 'region','email');
            }))->get()
            ->pluck('client')
            ->toArray();

        Log::info($this->signature . ' - sending NPS emails');
        Log::info(print_r($users, TRUE));

        $variables = [
            "redirect_url" => "https://www.google.com",
            "name" => "test NPS"
        ];
        print_r($users);
        foreach ($users as $user) {
            if($user['region']==='uk' || $user['region']==='co'){
                Log::info('<======== UK or CO ========>');
                $response = $this->sendMailViaMailjet("support@snapmed.co.uk", $user['email'], (int) env('MJ_NPS_UK_TEMPLATE_ID'), $variables);
                if ($response->success()) {
                    Log::info('NPS Email sent to ' . $user['email']);
                }else{
                    Log::info('NPS Email failed for ' . $user['email']);
                    Log::error(print_r($response));
                }
            }
            elseif($user['region']==='no'){
                Log::info('<======== NO ========>');
                $response = $this->sendMailViaMailjet("support@snapmed.no", $user['email'], (int) env('MJ_NPS_NO_TEMPLATE_ID'), $variables);
                if ($response->success()) {
                    Log::info('NPS Email sent to ' . $user['email']);
                }else{
                    Log::info('NPS Email failed for ' . $user['email']);
                    Log::error(print_r($response));
                }

            }
            elseif($user['region']==='se'){
                Log::info('<======== SE ========>');
                $response = $this->sendMailViaMailjet("support@snapmed.se", $user['email'], (int) env('MJ_NPS_SE_TEMPLATE_ID'), $variables);
                if ($response->success()) {
                    Log::info('NPS Email sent to ' . $user['email']);
                }else{
                    Log::info('NPS Email failed for ' . $user['email']);
                    Log::error(print_r($response));
                }

            }
            
        }
        
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
