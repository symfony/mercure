CHANGELOG
=========

0.6.0
-----

* Add `mercure()` Twig function to generate URLs of the hubs and set the authorization cookies
* Add `Authorization::setCookie()` to ease setting authorization cookies
* Add `Authorization::clearCookie()` to remove the `mercureAuthorization` cookie from the browser
* Fix the domain check in `Authorization::createCookie()` to allow subdomains
* Compatibility with Symfony 6 and Symfony Contracts 3

0.5.3
-----

* Full compatibility with PHP 7.1+

0.5.2
-----

* Set a default expiration for the JWT and the cookie when using the `Authorization` class

0.5.1
-----

* Fix `MockHub::__construct()` signature

0.5.0
-----

* Added `Symfony\Component\Mercure\Jwt\TokenProviderInterface`
* Added `Symfony\Component\Mercure\Jwt\TokenFactoryInterface`
* Added `Symfony\Component\Mercure\Jwt\StaticTokenProvider`
* Added `Symfony\Component\Mercure\Jwt\CallabkeTokenProvider`
* Added `Symfony\Component\Mercure\Jwt\LcobucciTokenFactory`
* Added `Symfony\Component\Mercure\Jwt\FactoryTokenProvider`
* Added `Symfony\Component\Mercure\Messenger\UpdateHandler`
* Added `Symfony\Component\Mercure\Hub`
* Added `Symfony\Component\Mercure\HubInterface`
* Added `Symfony\Component\Mercure\HubRegistry`
* Added `Symfony\Component\Mercure\Discovery`
* Added `Symfony\Component\Mercure\Authorization`
* Deprecated `Jwt\StaticJwtProvider`, use `Jwt\StaticTokenProvider` instead.
* Deprecated `PublisherInterface` interface in favor of `HubInterface`.
* Deprecated `Publisher` class in favor of `Hub`.
* Deprecated `Debug\TraceablePublisher` class in favor of `Debug\TraceableHub`.

0.4.1
-----

* Compatibility with PHP 8

0.4.0
-----

* Compatibility with Mercure 0.10

0.3.0
-----

* Compatibility with Symfony 5
* Add `TraceablePublisher` to collect debug information
* Add `PublisherInterface`
* Fix an error when using the `retry` parameter

0.2.0
-----

* Use the Symfony HttpClient component
