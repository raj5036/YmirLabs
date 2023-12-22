<?php

namespace App\Console\Commands;

use Log;

use App\Examination;
use App\Notification;
use App\Notifications\SmsSender;
use Carbon\Carbon;

use Illuminate\Console\Command;

class NotifyNewCases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:new-cases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification(s) to doctors if new cases within the last 4 hours';

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
        $lastCheck = Carbon::now(env('APP_TIMEZONE'))->subHours(8);

        // Check if there are any new cases created after the lastCheck.
        $regions = Examination::where('complete', '=', true)
            ->where('diagnosed', '=', false)
            ->where(function ($query) {
                $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                $query->orWhere(function ($orQuery) {
                    $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                    $orQuery->whereNotNull('charged');
                    $orQuery->whereNotNull('stripe');
                });
            })
            ->whereNull('locked_by')
            ->where('category', '!=', 'video')
            ->where('created_at', '>', $lastCheck)
            ->with(array('client' => function ($query) {
                $query->select('uuid', 'region');
            }))->get()
            ->unique('client.region')
            ->pluck('client.region')
            ->toArray();

        Log::info($this->signature . ' - sending notification: ' . ($regions ? 'Yes' : 'No'));

        if($regions) {
            $notifications = Notification::where('type', 'new-cases')
                ->whereHas('physician', function ($query) use ($regions) {
                    $query->where(function ($q) use ($regions) {
                        foreach ($regions as $region) {
                            $q->orWhereJsonContains('servable_regions', $region);
                        }
                    });
                })->get();
            foreach ($notifications as $key => $notification) {
                $this->sendNotificationToDoctor($notification->physician->phonenumber, $notification->physician->country);
                $this->info("Sending new-case sms to: {$notification->physician->phonenumber}");
            }
        }
        return true;
    }

    protected function sendNotificationToDoctor($phonenumber, $region)
    {
        $prefix = env('APP_ENV') === 'production' ? 'Snapmed:' : 'TEST Snapmed:';
        $message = "$prefix New case\n\nWe have received a new case that is ready for processing.";
        try {
            SmsSender::sendSms($phonenumber, $message, $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }
}
