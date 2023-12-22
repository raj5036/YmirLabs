<?php

namespace App\Console\Commands;

use Log;

use App\Examination;
use App\Notification;
use App\Notifications\SmsSender;
use Carbon\Carbon;

use Illuminate\Console\Command;

class NotifyDeadline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:deadline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send out notifications to doctors where cases are within 2 hours of their deadline';

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
        $twoHoursAhead = Carbon::now(env('APP_TIMEZONE'))->addHours(2);

        // Check if there are cases that hit their deadline within the next twoHoursAhead.
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
            ->where('deadline_time', '<=', $twoHoursAhead)
            ->whereNotIn('uuid', function ($query)  {
                $query->select('examination')
                    ->from('diagnoses');
            })
            ->with(array('client' => function ($query) {
                $query->select('uuid', 'region');
            }))->get()
            ->unique('client.region')
            ->pluck('client.region')
            ->toArray();

        Log::info($this->signature . ' - sending notification: ' . ($regions ? 'Yes' : 'No'));

        if($regions) {
            $notifications = Notification::where('type', 'deadline')
                ->whereHas('physician', function ($query) use ($regions) {
                    $query->where(function ($q) use ($regions) {
                        foreach ($regions as $region) {
                            $q->orWhereJsonContains('servable_regions', $region);
                        }
                    });
                })->get();
            foreach ($notifications as $key => $notification) {
                $this->sendNotificationToDoctor($notification->physician->phonenumber, $notification->physician->country);
                $this->info("Sending deadline notification to: {$notification->physician->phonenumber}");
            }
        }
        return true;
    }

    protected function sendNotificationToDoctor($phonenumber, $region)
    {
        $prefix = env('APP_ENV') === 'production' ? 'Snapmed:' : 'TEST Snapmed:';
        $message = "$prefix Deadline Warning\n\nWe have at least one case with LESS than two hours left before the deadline expires.";
        try {
            SmsSender::sendSms($phonenumber, $message, $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }}
