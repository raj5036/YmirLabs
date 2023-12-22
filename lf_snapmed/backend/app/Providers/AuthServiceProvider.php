<?php

namespace App\Providers;

use App\User;
use App\MedUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->viaRequest('api', function ($request) {
            try {
                return $request->auth;
            } catch(Exception $e) {
                return null;
            }
        });
    }
}
