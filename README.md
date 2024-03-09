# Laravel Logins 🔑

- Track each login, attaching information of the device (device type, device name, OS, browser, IP address) and the context (date, location)
- Save this information in your database, allowing you to display it in user's account
- Notify users on new login, emailing them with the information collected
- Offer your users the ability to log out a specific device, all the devices except the current one, or all at once.

_____

* [Compatibility](#compatibility)
* [Installation](#installation)
  * [Prepare your authenticatable models](#prepare-your-authenticatable-models)
  * [Choose and install a user-agent parser](#choose-and-install-a-user-agent-parser)
  * [Configure the user provider](#configure-the-user-provider)
  * [Laravel Sanctum](#laravel-sanctum)
* [Usage](#usage)
  * [Retrieving the logins](#retrieving-the-logins)
    * [Get all the logins](#get-all-the-logins)
    * [Get the current login](#get-the-current-login)
  * [Check for the current login](#check-for-the-current-login)
  * [Revoking logins](#revoking-logins)
    * [Revoke a specific login](#revoke-a-specific-login)
    * [Revoke all the logins](#revoke-all-the-logins)
    * [Revoke all the logins except the current one](#revoke-all-the-logins-except-the-current-one)
* [IP address geolocation](#ip-address-geolocation)
* [Events](#events)
  * [LoggedIn](#loggedin)
* [License](#license)

## Compatibility

- This package has been tested with Laravel 10 and 11

- It works with all the session drivers supported by Laravel, except the cookie driver which saves the sessions only in
  the client browser and the array driver

- It also supports personal API tokens provided by **Laravel Sanctum (v3)**

## Installation

Install the package with composer:

```bash
composer require alajusticia/logins
```

Publish the configuration file (`logins.php`) with:

```bash
php artisan vendor:publish --tag="logins-config"
```

Run the `logins:install` command (this will run the required database migrations):

```bash
php artisan logins:install
```

### Prepare your authenticatable models

In order to track the logins of your app's users, add the `ALajusticia\Logins\Traits\HasLogins` trait
on your authenticatable models that you want to track:

```php
use ALajusticia\Logins\Traits\HasLogins;
use Illuminate\Foundation\Auth\User as Authenticatable;
// ...

class User extends Authenticatable
{
    use HasLogins;

    // ...
}
```

### Choose and install a user-agent parser

This package relies on a user-agent parser to extract the information.

It supports the two most popular parsers:
- WhichBrowser ([https://github.com/WhichBrowser/Parser-PHP](https://github.com/WhichBrowser/Parser-PHP))
- Agent ([https://github.com/jenssegers/agent](https://github.com/jenssegers/agent))

Before using Laravel Logins, you need to choose a supported parser, install it and indicate in the configuration file 
which one you want to use.

### Configure the user provider

This package comes with a modified Eloquent user provider that retrieve remembered users from the logins table, allowing 
each session to have its own remember token and giving us the ability to revoke sessions individually.

In your `auth.php` configuration file, use the `logins` driver in the user providers list for the users you want to track:

```php
'providers' => [
    'users' => [
        'driver' => 'logins',
        'model' => App\Models\User::class,
    ],
    
    // ...
],
```

### Laravel Sanctum

In addition to sessions, Laravel Logins also support personal access tokens issued by Laravel Sanctum 
(go to [Compatibility](#compatibility) section for information on supported versions).

If Laravel Sanctum is installed after you've installed Laravel Logins, you will have to run the `logins:install` 
command again to update your installation.

## Usage

The `ALajusticia\Logins\Traits\HasLogins` trait provided by this package surcharge your authenticatable models with 
methods to list their logins and to give you full individual control on them.

Everytime a successful authentication occurs, information will automatically be saved in the database in the `logins` table.

Also, if a notification class is defined in the `logins.php` configuration file, an email will be sent to notify 
your user on every new login.

### Retrieving the logins

#### Get all the logins

```php
$logins = request()->user()->logins;
```

#### Get the current login

```php
$currentLogin = request()->user()->current_login;
```

### Check for the current login

Each login instance comes with a dynamic `is_current` attribute.

It's a boolean that indicates if the login instance corresponds to the login related to the current session or current
personal access token used.

### Revoking logins

#### Revoke a specific login

Using our custom user provider, you have the ability to log out a specific device, because each session has its own 
remember token.

To revoke a specific login, use the `logout` method with the ID of the login you want to revoke. 
If no parameter is given, the current login will be revoked.

```php
request()->user()->logout(1); // Revoke the login where id=1
```

```php
request()->user()->logout(); // Revoke the current login
```

#### Revoke all the logins

We can destroy all the sessions and revoke all the Sanctum tokens by using the `logoutAll` method. 
Useful when, for example, the user's password is modified, and we want to log out all the devices.

This feature destroys all sessions, even the remembered ones.

```php
request()->user()->logoutAll();
```

#### Revoke all the logins except the current one

The `logoutOthers` method acts in the same way as the `logoutAll` method, except that it keeps the current
session or Sanctum token alive.

```php
request()->user()->logoutOthers();
```

## IP address geolocation

In addition to the information extracted from the user-agent, you can collect information about the location, based on
the client's IP address.

To use this feature, you have to install and configure this package: [https://github.com/stevebauman/location](https://github.com/stevebauman/location).
Then, enable the IP address geolocation in the `logins.php` configuration file.

By default, this is how the client's IP address is determined:

```php
// Supports Cloudflare proxy by checking if HTTP_CF_CONNECTING_IP header exists
// Fallback to built-in Laravel ip() method in Request

return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? request()->ip();
```

You can define your own IP address resolution logic, by passing a closure to the `getIpAddressUsing()` static method of
the `ALajusticia\Logins\Logins` class, and returning the resolved IP address.

Call it in the `boot()` method of your `App\Providers\AppServiceProvider`:

```php
\ALajusticia\Logins\Logins::getIpAddressUsing(function () {
    return request()->ip();
});
```

## Events

### LoggedIn

On a new login, you can listen to the event `ALajusticia\Logins\Events\LoggedIn`.

It receives the authenticated model (in `$authenticatable` property) and a `ALajusticia\Logins\RequestContext` object 
(in `$context` property) containing all the information collected on the request:

```php
use ALajusticia\Logins\Events\LoggedIn;
use Illuminate\Support\Facades\Event;

Event::listen(function (LoggedIn $event) {
    
    // Methods available in RequestContext:
    $event->context->userAgent(); // Returns the full, unparsed, User-Agent header
    $event->context->ipAddress(); // Returns the client's IP address
    $event->context->parser(); // Returns the parser used to parse the User-Agent header
    $event->context->location(); // Returns the location (Stevebauman\Location\Position object), if IP address geolocation enabled
    
    // Methods available in the parser:
    $this->context->parser()->getDevice(); // The name of the device
    $this->context->parser()->getDeviceType(); // The type of the device (desktop, mobile, tablet or phone)
    $this->context->parser()->getPlatform(); // The name of the platform/OS
    $this->context->parser()->getBrowser(); // The name of the browser
})
```

## Notifications

If you want to send a notification to your users when a new login occurs with their account, pass a notification class
to the `new_login_notification` option in the `logins.php` configuration file.

Laravel Logins comes with a ready-to-use notification (`ALajusticia\Logins\Notifications\NewLogin`),
or you can use your own.

## Translations

This packages comes with translations for English, Spanish and French, used in the notifications.

If you want to customize the translations or add new ones, you can publish the language files by running this command:

```bash
php artisan vendor:publish --provider="ALajusticia\Localized\LocalizedServiceProvider" --tag="lang"
php artisan vendor:publish --tag="logins-lang"
```

## License

Open source, licensed under the [MIT license](LICENSE).
