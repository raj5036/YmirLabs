<?php

namespace App\Console\Commands;

use App\MedUser;
use App\Notifications\SmsSender;
use Illuminate\Console\Command;

class UserCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new medical user';

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
        $user = new MedUser();
        $fillables = $user->getFillable();
        foreach ($fillables as $key => $fillable) {
            if ($fillable == 'password') {
                $passwd = $this->secret(($key+1) . "/" . count($fillables) . " User $fillable");
                $user->password = app('hash')->make($passwd);
            } else if ($fillable == 'servable_regions') {
                $user->$fillable = "[" . $this->ask(($key+1) . "/" . count($fillables) . " User $fillable : \"region1\", \"region2\", \"region3\"") . "]";
            } else {
                $user->$fillable = $this->ask(($key+1) . "/" . count($fillables) . " User $fillable");
            }
        }
        $passwordHelp = $this->secret('Password help');
        if ($this->confirm("Do you want to create the user?", true)) {
            $user->save();

            try {
                $message = "Det har blitt opprettet en bruker til deg på Snapmed. \n\nPassordet ditt er: $passwd $passwordHelp";
                SmsSender::sendSms($user->phonenumber, $message, $user->region);
            } catch (Exception $ex) {
                Log::error($ex);
                throw $ex;
            }

            $this->info("User created (uuid: {$user->uuid})");
        }
        return true;
    }
}
