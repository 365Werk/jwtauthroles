# JWT Auth and Roles

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](license.md) [![Join the chat at https://gitter.im/werk365/Laravel-JWT-Auth-Roles](https://badges.gitter.im/werk365/Laravel-JWT-Auth-Roles.svg)](https://gitter.im/werk365/Laravel-JWT-Auth-Roles?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

![StyleCI][ico-styleci]
[![Scrutinizer Quality][ico-scrutinizer]][link-scrutinizer]
![Tests](https://github.com/365Werk/Laravel-JWT-Auth-Roles/workflows/Run%20Tests/badge.svg)


Made to use fusionauth users in laravel using JWT. Possible to either use pem keys directly or use the jwks endpoint.

User roles from JWT can be converted to spatie/laravel-permissions roles automatically so the package can be used normally for permissions.

Even though it was made for fusionauth, should be quite general purpose for using JWTs/ jwks and roles in laravel.

.

Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

``` bash
$ composer require werk365/jwtauthroles
```

Publish config and migration

```bash
$ php artisan vendor:publish --provider="werk365\jwtauthroles\jwtAuthRolesServiceProvider"
```

Run migration
```bash
$ php artisan migrate
```

## Usage

In your AuthServiceProvider modify boot()
```php
use werk365\jwtauthroles\jwtAuthRoles;

public function boot()
{
    $this->registerPolicies();

    Auth::viaRequest('jwt', function ($request) {
        return jwtAuthRoles::authUser($request);
    });
}
```

Then either change one of your guards in config/auth.php to use the jwt driver, or add a new guard
```php
'jwt' => [
    'driver' => 'jwt',
    'provider' => 'users',
    'hash' => false,
],
```
Now you can use the JWT guard in your routes, for example on a group:
```php
Route::group(['middleware' => ['auth:jwt']], function () {
    // Routes can go here
});
```

If you do not use laravel-permission by spatie make sure to disable those features in the config. 
Also make sure to disable creating new users if user column have no default values and you wish to use more column in the users table than just the uuid column.
By default the uuid will be put in the 'id' column, make sure this supports uuids. It's also possible to define a different uuid column in the config and have regular incrementing IDs. 

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

Testing is not yet implemented

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email <hergen.dillema@gmail.com> instead of using the issue tracker.

## Credits

- [Hergen Dillema][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/werk365/jwtauthroles.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/werk365/jwtauthroles.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/werk365/jwtauthroles/master.svg?style=flat-square
[ico-styleci]: https://github.styleci.io/repos/278075608/shield
[ico-scrutinizer]: https://scrutinizer-ci.com/g/365Werk/Laravel-JWT-Auth-Roles/badges/quality-score.png

[link-packagist]: https://packagist.org/packages/werk365/jwtauthroles
[link-downloads]: https://packagist.org/packages/werk365/jwtauthroles
[link-scrutinizer]: https://scrutinizer-ci.com/g/365Werk/Laravel-JWT-Auth-Roles/
[link-author]: https://github.com/HergenD
[link-contributors]: ../../contributors
