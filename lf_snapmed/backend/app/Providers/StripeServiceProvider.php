<?php

namespace App\Providers;

use Stripe\Stripe;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class StripeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
