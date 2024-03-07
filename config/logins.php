<?php

return [

    // Configuration file for Laravel Logins package
    // https://github.com/alajusticia/laravel-logins

    /*
    |--------------------------------------------------------------------------
    | Remember token lifetime
    |--------------------------------------------------------------------------
    |
    | Here you can specify the lifetime of the remember tokens.
    |
    | Must be an integer representing the number of days, or null to keep the
    | user authenticated indefinitely or until they manually log out.
    |
    */

    'remember_token_lifetime' => 365, // 1 year

    /*
    |--------------------------------------------------------------------------
    | Parser
    |--------------------------------------------------------------------------
    |
    | Choose which parser to use to parse the User-Agent.
    | You will need to install the package of the corresponding parser.
    |
    | Supported values:
    | 'agent' (see https://github.com/jenssegers/agent)
    | 'whichbrowser' (see https://github.com/WhichBrowser/Parser-PHP)
    |
    */

    'parser' => 'whichbrowser',

    /*
    |--------------------------------------------------------------------------
    | IP address geolocation
    |--------------------------------------------------------------------------
    |
    | Add geolocation information to tracked logins.
    |
    | This feature uses this package: https://github.com/stevebauman/location
    | Refer to its documentation to configure a driver.
    |
    */

    'ip_geolocation' => [

        /*
        |----------------------------------------------------------------------
        | Environments
        |----------------------------------------------------------------------
        |
        | Indicate here an array of environments for which you want to enable
        | IP address geolocation.
        |
        | Empty it to totally disable the feature.
        |
        */

        'environments' => [
            // 'production',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | New login notification
    |--------------------------------------------------------------------------
    |
    | Register here the notification class to use to notify the user when a
    | new login occurs.
    |
    | Laravel Logins comes with a ready-to-use notification, or you can use
    | your own.
    |
    */

    'new_login_notification' => \ALajusticia\Logins\Notifications\NewLogin::class,

    /*
    |--------------------------------------------------------------------------
    | Security page route
    |--------------------------------------------------------------------------
    |
    | Indicate here the route name for the page where users can find security
    | settings (such as password modification and a list of recent sessions).
    | If defined, a URL to your security page will be included in email
    | notifications.
    |
    */

    'security_page_route' => null,
];
