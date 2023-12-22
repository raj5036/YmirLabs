<?php

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Application Routes
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('/',                       ['as' => 'version',                         function () use ($router) { return $router->app->version(); }]);
    $router->post('/terms',                  ['as' => 'terms',                           'uses' => 'StaticResourceController@getTerms']);
    $router->post('/privacy',                ['as' => 'privacy',                         'uses' => 'StaticResourceController@getPrivacyText']);
    $router->post('/stripe-webhook',        ['as' => 'stripeWebhook',                   'uses' => 'WebHookController@stripeWebhook']);
    $router->post('/update-password',       ['as' => 'updatePassword',                  'uses' => 'UserController@updatePassword']);
    
    $router->post('/upload',                ['as' => 'upload',                          'uses' => 'ApiController@upload']);
    $router->post('/doctors',               ['as' => 'doctors',                         'uses' => 'ApiController@doctors']);
    $router->post('/check',                 ['as' => 'check',                           'uses' => 'ApiController@check']);
    $router->post('/recheck',               ['as' => 'recheck',                         'uses' => 'ApiController@recheck']);
    $router->post('/feedback',              ['as' => 'feedback',                        'uses' => 'ApiController@feedback']);
    $router->post('/login',                 ['as' => 'login',                           'uses' => 'AuthController@login']);
    $router->post('/login-storebrand',      ['as' => 'loginStorebrand',                 'uses' => 'AuthController@loginStorebrand']);
    $router->post('/init-bankid',           ['as' => 'initBankId',                      'uses' => 'AuthController@initBankId']);
    $router->post('/login-bankid',          ['as' => 'loginBankId',                     'uses' => 'AuthController@loginBankId']);
    $router->post('/partner-login',         ['as' => 'partnerLogin',                    'uses' => 'AuthController@partnerLogin']);
    $router->post('/login-or-create',       ['as' => 'loginOrCreateUser',               'uses' => 'AuthController@loginOrCreateUser']);
    $router->post('/login-aurora',          ['as' => 'loginAurora',                     'uses' => 'AuthController@loginAurora']);
    $router->post('/verify-email',          ['as' => 'verifyEmail',                     'uses' => 'AuthController@verifyEmail']);
    $router->post('/login-phone',           ['as' => 'loginPhone',                      'uses' => 'AuthController@loginPhone']);
    $router->post('/verify-otp',            ['as' => 'verifyOTP',                       'uses' => 'AuthController@verifyOTP']);
    $router->post('/submit-bug',            ['as' => 'submitBug',                       'uses' => 'ApiController@submitBug']);
});

$router->group(['prefix' => 'api', 'middleware' => 'cors'], function () use ($router) {
    $router->post('/email-subscription',['as' => 'emailSubscription',               'uses' => 'UserController@emailSubscription']);
});

$router->group(['prefix' => 'api', 'middleware' =>  'auth'], function () use ($router) {
    $router->get('/download-referral/{uuid}',       ['as' => 'downloadReferral',                        'uses' => 'StaticResourceController@downloadReferral']);
    $router->post('/user-book-video',               ['as' => 'userBookVideo',                           'uses' => 'VideoController@userBookVideo']);
    
    $router->post('/user-set-phone',                ['as' => 'userSetPhone',                            'uses' => 'UserController@userSetPhone']);
    $router->post('/set-email',                     ['as' => 'setEmail',                                'uses' => 'UserController@setEmail']);
    $router->post('/change-password',               ['as' => 'changePassword',                          'uses' => 'UserController@changePassword']);
    $router->post('/verify/{otp}',                  ['as' => 'verify',                                  'uses' => 'AuthController@verify']);
    $router->post('/forgot-password',               ['as' => 'forgot-password',                         'uses' => 'AuthController@forgotPassword']);
    $router->get('/user-email-verified',            ['as' => 'checkVerifiedEmail',                      'uses' => 'AuthController@checkVerifiedEmail']);
    $router->post('/verify-password',               ['as' => 'verifyPassword',                          'uses' => 'AuthController@verifyPassword']);
    $router->get('/user',                           ['as' => 'user',                                    'uses' => 'ApiController@user']);
    $router->post('/user-submit-check',             ['as' => 'userSubmitCheck',                         'uses' => 'ApiController@userSubmitCheck']);
    $router->post('/confirm-partner-examination',   ['as' => 'confirmPartnerExamination',               'uses' => 'ApiController@confirmPartnerExamination']);
    $router->post('/getpromo',                      ['as' => 'getpromo',                                'uses' => 'ApiController@getpromo']);
    $router->post('/checkpromo',                    ['as' => 'checkpromo',                              'uses' => 'ApiController@checkpromo']);
    $router->post('/charge',                        ['as' => 'charge',                                  'uses' => 'ApiController@charge']);
    $router->post('/confirm-question-payment',      ['as' => 'confirm-question-payment',                'uses' => 'ApiController@question_confirm_payment']);
    $router->post('/images',                        ['as' => 'images',                                  'uses' => 'ApiController@images']);
    $router->post('/second',                        ['as' => 'second',                                  'uses' => 'ApiController@second']);
    $router->get('/cases',                          ['as' => 'cases',                                   'uses' => 'ApiController@cases']);
    $router->get('/cases/{uuid}',                   ['as' => 'cases.view',                              'uses' => 'ApiController@casesView']);
    $router->get('/doctors/{uuid}',                 ['as' => 'doctor',                                   'uses' => 'ApiController@doctorView']);
    $router->post('/submit-image-case',             ['as' => 'submitImageCase',                         'uses' => 'ApiController@submitImageCase']);
    $router->get('/check-examination',              ['as' => 'checkExamination',                        'uses' => 'ApiController@checkExamination']);
    $router->post('/user-update',                   ['as' => 'userUpdate',                              'uses' => 'ApiController@userUpdate']);
    $router->post('/examination',                   ['as' => 'examination',                             'uses' => 'ApiController@examination']);
    $router->post('/examination-update',            ['as' => 'examinationUpdate',                       'uses' => 'ApiController@examinationUpdate']);
    $router->post('/user-additional-data',          ['as' => 'userAdditionalData',                      'uses' => 'ApiController@userAdditionalData']);
    $router->post('/user-submit-check-uk',          ['as' => 'userSubmitCheckUK',                       'uses' => 'ApiController@userSubmitCheckUK']);
});


$router->group(['prefix' => 'api/video'], function () use ($router) {
    $router->get('/slots',               ['as' => 'slots',                           'uses' => 'VideoController@video_slots']);
    $router->get('/slots/{year}/{week}', ['as' => 'slots',                           'uses' => 'VideoController@video_slots']);
    $router->post('/confirm-charge',     ['as' => 'charge',                          'uses' => 'VideoController@video_charge']);
    $router->post('/confirm-payment',    ['as' => 'confirm-payment',                 'uses' => 'VideoController@video_confirm_payment']);
    $router->post('/checkpromo',         ['as' => 'checkpromo',                      'uses' => 'VideoController@video_checkpromo']);
});

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Administration routes
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

$router->group(['prefix' => 'admin/api'], function () use ($router) {
    $router->post('/login',                 ['as' => 'admin.login',                 'uses' => 'Admin\ExaminationController@login']);
    $router->get('/refresh-token',          ['as' => 'admin.refreshToken',          'uses' => 'Admin\ExaminationController@refreshToken']);
    $router->post('/send-otp',              ['as' => 'admin.resetPasswordSendOtp',  'uses' => 'Admin\ExaminationController@resetPasswordSendOtp']);
    $router->post('/update-password',       ['as' => 'admin.updatePassword',        'uses' => 'Admin\ExaminationController@updatePassword']);
    $router->post('/verify-email-token',    ['as' => 'admin.verifyEmailToken',      'uses' => 'Admin\ExaminationController@verifyEmailToken']);
    $router->post('/set-password',          ['as' => 'admin.setPassword',           'uses' => 'Admin\ExaminationController@setPassword']);
});

$router->group(['prefix' => 'admin/api', 'middleware' => 'med_auth'], function () use ($router) {
    $router->post('/verify/{otp}',                                      ['as' => 'verify',                          'uses' => 'Admin\ExaminationController@verify']);
    $router->get('/examinations',                                       ['as' => 'examinations',                    'uses' => 'Admin\ExaminationController@index']);
    $router->get('/stats',                                              ['as' => 'stats',                           'uses' => 'Admin\ExaminationController@stats']);
    $router->get('/next',                                               ['as' => 'examination.next',                'uses' => 'Admin\ExaminationController@next']);
    $router->post('/examination/{uuid}/newimages',                      ['as' => 'examination.newimages',           'uses' => 'Admin\ExaminationController@newimages']);
    $router->post('/diagnosis/{uuid}',                                  ['as' => 'diagnosis.update',                'uses' => 'Admin\ExaminationController@diagnosis']);
    $router->get('/cases',                                              ['as' => 'interesting.cases',               'uses' => 'Admin\ExaminationController@cases']);
    $router->get('/interesting/{uuid}',                                 ['as' => 'interesting.get',                 'uses' => 'Admin\ExaminationController@interesting']);
    $router->post('/interesting/{uuid}',                                ['as' => 'interesting.save',                'uses' => 'Admin\ExaminationController@interestingUpdate']);
    $router->post('/diagnosis/update-private-description/{uuid}',       ['as' => 'update.private-description',      'uses' => 'Admin\ExaminationController@updatePrivateDescription']);
    $router->post('/search',                                            ['as' => 'search',                          'uses' => 'Admin\ExaminationController@search']);
    $router->get('/search-by-case/{code}',                              ['as' => 'search.case',                     'uses' => 'Admin\ExaminationController@searchByCase']);
    $router->get('/patient/{uuid}',                                     ['as' => 'patient.view',                    'uses' => 'Admin\ExaminationController@patient']);
    $router->post('/lock',                                              ['as' => 'exmination.lock',                 'uses' => 'Admin\ExaminationController@lock']);
    $router->put('/examination/{uuid}/unlock',                          ['as' => 'exmination.unlock',               'uses' => 'Admin\ExaminationController@unlock']);
    $router->post('/invite-user',                                       ['as' => 'invite.users',                    'uses' => 'Admin\ExaminationController@inviteUser']);
    $router->post('/change-password',                                   ['as' => 'exmination.changePassword',       'uses' => 'Admin\ExaminationController@changePassword']);
    $router->get('/icd-code',                                           ['as' => 'exmination.getBlankIcdCodes',     'uses' => 'Admin\ExaminationController@getBlankIcdCodes']);
});

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Superadmin routes
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

$router->group(['prefix' => 'admin/api', 'middleware' => 'med_auth:superadmin'], function () use ($router) {
    $router->post('/examination/export',            ['as' => 'exmination.export',       'uses' => 'Admin\ExaminationController@export']);
});

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Digicare routes
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

$router->group(['prefix' => 'digicare/api', 'middleware' => 'digicare_auth'], function () use ($router) {
    $router->get('/services',                       ['as' => 'digicare.services',             'uses' => 'External\DigicareController@services']);
    $router->get('/specialists',                    ['as' => 'digicare.specialists',          'uses' => 'External\DigicareController@specialists']);
    $router->get('/timeslots',                      ['as' => 'digicare.timeslots',            'uses' => 'External\DigicareController@timeslots']);
    $router->post('/bookings',                      ['as' => 'digicare.bookings',             'uses' => 'External\DigicareController@bookings']);
});

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
