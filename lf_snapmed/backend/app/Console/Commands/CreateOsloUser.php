<?php

namespace App\Console\Commands;

use App\Notifications\SmsSender;
use App\User;
use Illuminate\Console\Command;

class CreateOsloUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:oslo 
                            {phone : Formatted Phone number e.g +47 123 45 678} 
                            {password : Password for user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user with phonenumber and password';

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
        $phone = $this->argument('phone');
        $password = $this->argument('password');

        User::create(
            [
                'phonenumber' => $phone,
                'password' => app('hash')->make($password),
                'login_method' => User::LOGIN_METHOD_PHONE,
                'locale' => 'nb',
                'region' => 'no',
                'partner' => 'Oslo'
            ]
        );

        try {
            $message = "Det har blitt opprettet en bruker til deg på Snapmed. \n\nPassordet ditt er: $password";
            SmsSender::sendSms($phone, $message, 'no');
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }

        return true;
    }
}
