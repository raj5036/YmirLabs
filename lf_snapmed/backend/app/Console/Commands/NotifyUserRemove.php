<?php

namespace App\Console\Commands;

use App\MedUser;
use App\Notification;

use Illuminate\Console\Command;

class NotifyUserRemove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:user-remove {type} {uuid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an existing user from the notification table';

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

        $notification = Notification::where('type', $type)->where('med_user', $medUser->uuid)->firstOrFail();
        $notification->delete();
        $this->info("User (uuid: {$medUser->uuid}) removed from $type notifications");
        return true;
    }
}
