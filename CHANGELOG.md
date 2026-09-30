# Changelog

All notable changes to Laravel Logins are documented in this file.

The project follows [Semantic Versioning](https://semver.org/).

## [v1.9.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.9.1) - 2026-09-30

### Fixed

- Configure dedicated database connection through `LOGINS_DB_CONNECTION` instead of `SESSION_CONNECTION` (It was a remnant of the package's original implementation, when tracked logins were coupled to session storage. Login records are now stored independently as Eloquent models). When left unset, login records use the application's default database connection.

### Upgrade Notes

1. If `config/logins.php` is already published, replace `'database_connection' => env('SESSION_CONNECTION')` with `'database_connection' => env('LOGINS_DB_CONNECTION')`.
2. If login records should use a non-default relational database connection, set `LOGINS_DB_CONNECTION` to that connection's configured name.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.9.0...v1.9.1)

## [v1.9.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.9.0) - 2026-09-29

### Changed

- Resolve login IP addresses through Laravel's current request and trusted-proxy configuration.
- Allow `Logins::ipAddress()` and custom IP resolvers to return `null` when no client address is available.

### Security

- Stop reading `CF-Connecting-IP` directly from PHP server globals, preventing untrusted or stale Cloudflare headers from overriding Laravel's resolved client address.
- Resolve the current request at call time so IP collection remains safe under long-running workers such as Laravel Octane.

### Documentation

- Document trusted-proxy configuration, header-spoofing risks, and application-specific IP resolvers.

### Upgrade Notes

Applications behind Cloudflare, a load balancer, or another reverse proxy must configure that proxy through Laravel's trusted-proxy support. The package no longer trusts `CF-Connecting-IP` automatically.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.8.2...v1.9.0)

## [v1.8.2](https://github.com/alajusticia/laravel-logins/releases/tag/v1.8.2) - 2026-08-12

### Fixed

- Account for throttled activity updates in last-active labels.

### Documentation

- Document how to disable login notifications globally.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.8.1...v1.8.2)

## [v1.8.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.8.1) - 2026-07-23

### Changed

- Use Lucide v1 in the published Logins component stub.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.8.0...v1.8.1)

## [v1.8.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.8.0) - 2026-07-22

### Added

- Add optional throttling for `last_activity_at` updates during session and Sanctum token tracking ([#17](https://github.com/alajusticia/laravel-logins/pull/17), thanks [@mekadalibrahem](https://github.com/mekadalibrahem)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.7.3...v1.8.0)

## [v1.7.3](https://github.com/alajusticia/laravel-logins/releases/tag/v1.7.3) - 2026-07-22

### Added

- Add a `user_agent_max_length` configuration option. Stored raw User-Agent values are capped at `1024` bytes by default.

### Fixed

- Prevent long `User-Agent` headers from exceeding the previous `varchar(255)` database column limit.
- Store `logins.user_agent` as `TEXT` for new and existing installations.
- Make login-tracking writes best-effort so failed audit inserts no longer block session login or Sanctum token creation.

### Upgrade Notes

Run the migrations after updating:

```bash
php artisan migrate
```

If `config/logins.php` is already published, add:

```php
'user_agent_max_length' => 1024,
```

Set `user_agent_max_length` to `null` to store the complete User-Agent header.

## [v1.7.2](https://github.com/alajusticia/laravel-logins/releases/tag/v1.7.2) - 2026-04-19

### Security

- Update the vulnerable PHPUnit development dependency.

## [v1.7.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.7.1) - 2026-03-24

### Fixed

- Fix the path used when publishing translations ([#14](https://github.com/alajusticia/laravel-logins/pull/14), thanks [@stephenveach](https://github.com/stephenveach)).

## [v1.7.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.7.0) - 2026-03-15

### Added

- Add the `logins:publish` workflow for publishing starter-kit UI components ([#13](https://github.com/alajusticia/laravel-logins/pull/13)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.6.0...v1.7.0)

## [v1.6.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.6.0) - 2026-03-12

### Added

- Add scaffolding for the Laravel Vue Starter Kit ([#12](https://github.com/alajusticia/laravel-logins/pull/12)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.5.1...v1.6.0)

## [v1.5.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.5.1) - 2026-03-10

### Fixed

- Remove a Livewire stub emoji that caused Git warnings.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.5.0...v1.5.1)

## [v1.5.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.5.0) - 2026-03-09

### Added

- Add Laravel 13 compatibility ([#11](https://github.com/alajusticia/laravel-logins/pull/11)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.4.0...v1.5.0)

## [v1.4.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.4.0) - 2026-03-09

### Added

- Add a logins page for the Laravel Livewire Starter Kit ([#10](https://github.com/alajusticia/laravel-logins/pull/10)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.3.2...v1.4.0)

## [v1.3.2](https://github.com/alajusticia/laravel-logins/releases/tag/v1.3.2) - 2025-06-11

### Documentation

- Document how to disable login notifications.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.3.1...v1.3.2)

## [v1.3.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.3.1) - 2025-03-01

### Fixed

- Correct the `alajusticia/laravel-expirable` Composer constraint.

### Documentation

- Update the README.

## [v1.3](https://github.com/alajusticia/laravel-logins/releases/tag/v1.3) - 2025-03-01

### Added

- Add Laravel 12 support.

## [v1.2.6](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2.6) - 2024-11-16

### Fixed

- Make the `current_login` attribute available immediately after a remembered login by checking the session ID on the successful login event ([#6](https://github.com/alajusticia/laravel-logins/issues/6), thanks [@thomasakarlsen](https://github.com/thomasakarlsen)).

## [v1.2.5](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2.5) - 2024-10-25

### Fixed

- Fix Sanctum token authentication by using the authenticatable model supplied by authentication events ([#4](https://github.com/alajusticia/laravel-logins/issues/4)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.2.4...v1.2.5)

## [v1.2.4](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2.4) - 2024-10-25

### Fixed

- Use the authenticated user instead of `$this` while resolving login ownership ([#5](https://github.com/alajusticia/laravel-logins/pull/5), thanks [@thomasakarlsen](https://github.com/thomasakarlsen)).

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.2.3...v1.2.4)

## [v1.2.3](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2.3) - 2024-09-10

### Fixed

- Fix location serialization in `RequestContext::toArray()` ([#1](https://github.com/alajusticia/laravel-logins/issues/1)).

## [v1.2.2](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2.2) - 2024-07-29

### Added

- Add `trackLogins` and `notifyLogins` model properties for temporarily disabling login tracking or notifications.

## [v1.2.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2.1) - 2024-06-13

### Fixed

- Fix the install command declaring an option twice.

## [v1.2](https://github.com/alajusticia/laravel-logins/releases/tag/v1.2) - 2024-06-13

### Added

- Add the `--quiet` option to the install command.

## [v1.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.1) - 2024-04-29

### Added

- Make `RequestContext` implement Laravel's `Arrayable` contract.

### Changed

- Pass the array representation of `RequestContext` to the default notification.

## [v1.0.1](https://github.com/alajusticia/laravel-logins/releases/tag/v1.0.1) - 2024-04-26

### Changed

- Adjust property visibility in the default notification class.

[Full Changelog](https://github.com/alajusticia/laravel-logins/compare/v1.0...v1.0.1)

## [v1.0](https://github.com/alajusticia/laravel-logins/releases/tag/v1.0) - 2024-03-15

### Added

- Initial release.
