<?php

namespace App\Console\Commands;

use Log;

use App\Examination;
use App\Notification;
use App\Notifications\SmsSender;
use Carbon\Carbon;

use Illuminate\Console\Command;

class NotifyDeadlineFollowup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:deadline-followup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send out notifications to doctors where cases are within 2 hours of their deadline for followup cases';

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
        $examinations = Examination::where('complete', '=', true)
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
            ->whereIn('uuid', function ($query)  {
                $query->select('examination')->from('diagnoses');
            })
            ->get();

            
           
        Log::info($this->signature . ' - sending notification: ' . ($examinations ? 'Yes' : 'No'));

        // dd($examinations);

        if($examinations) {
            $doctors = array();
            foreach($examinations as $exam){
                $diag = $exam->diagnoses;
                foreach($diag as $d){
                    if(!in_array($d->physician->uuid, $doctors)){
                        array_push($doctors,$d->physician->uuid);
                    }
                }
            }

            var_dump($doctors);

            if($doctors) {
                $notifications = Notification::where('type', 'deadline')
                ->whereIn('med_user', $doctors)
                ->get();
                // var_dump($notifications);
                foreach ($notifications as $key => $notification) {
                    $this->sendNotificationToDoctor($notification->physician->phonenumber, $notification->physician->country);
                    $this->info("Sending deadline follow-up notification to: {$notification->physician->phonenumber}");
                }
            }
        }
        return true;
    }

    protected function sendNotificationToDoctor($phonenumber, $region)
    {
        $prefix = env('APP_ENV') === 'production' ? 'Snapmed:' : 'TEST Snapmed:';
        $message = "$prefix Deadline Warning\n\nWe have at least one follow-up case with LESS than two hours left before the deadline expires.";
        try {
            SmsSender::sendSms($phonenumber, $message, $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }}
