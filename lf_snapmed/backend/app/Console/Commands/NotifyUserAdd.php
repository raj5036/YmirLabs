<?php

namespace App\Console\Commands;

use App\MedUser;
use App\Notification;

use Illuminate\Console\Command;

class NotifyUserAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:user-add {type} {uuid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add an existing user to the nofications table';

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
        $type = $this->argument('type');
        $uuid = $this->argument('uuid');

        $medUser = MedUser::findOrFail($uuid);

        if ('new-cases' !== $type && 'deadline' !== $type) {
            throw new Exeption('Unknown type');
        }

        Notification::create([
            'type' => $type,
            'med_user' => $medUser->uuid,
        ]);
        $this->info("User (uuid: {$medUser->uuid}) added to $type notifications");
        return true;
    }
}
