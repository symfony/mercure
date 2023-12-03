CHANGELOG
=========

0.6.4
-----

* Allow `symfony/deprecation-contracts` v4

0.6.3
-----

* Compatibility with `lcobucci/jwt` 5.0

0.6.2
-----

* Always set the `Content-Type` HTTP header to `application/x-www-form-urlencoded` when sending an update to the hub
* `Symfony\Component\Mercure\Messenger\UpdateHandler` now returns the ID of the published update
* Allow passing `null` as `$subscribe` and `$publish` parameters in `Symfony\Component\Mercure\Jwt\TokenFactoryInterface`
* Add a new optional parameter in `Symfony\Component\Mercure\Authorization::__construct()` to set the `SameSite` cookie attribute

0.6.1
-----

* Allow passing additional data to the `mercure` JWT claim when using `Symfony\Component\Mercure\Token\LcobucciFactory` 
* Add a new `passphrase` argument to `Symfony\Component\Mercure\Token\LcobucciFactory` allowing the use of encrypted keys
* Add a new `lastEventId` option to the `mercure()` Twig function to set the `Last-Event-ID` query parameter
* Fix a bug preventing setting cookies for legit subdomains when using `Symfony\Component\Mercure\Authorization::createCookie()`
* Fix bug in `Symfony\Component\Mercure\Token\LcobucciFactory` that results in a runtime error when using "ecdsa" algorithms, alongside "lcobucci/jwt:^4.0"

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
