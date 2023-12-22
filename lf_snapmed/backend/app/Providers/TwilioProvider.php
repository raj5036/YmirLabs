<?php

namespace App\Providers;

use Twilio\Rest\Client;

use Illuminate\Support\ServiceProvider;

class TwilioProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('twilio', function() {
            return new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        });
    }
}
