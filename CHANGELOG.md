CHANGELOG
=========

0.5.3
-----

* Full compatibility with PHP 7.1+

0.5.2
-----

* Set a default expiration for the JWT and the cookie when using the `Authorization` class

0.5.1
-----

* fix `MockHub::__construct()` signature

0.5.0
-----

* added `Symfony\Component\Mercure\Jwt\TokenProviderInterface`
* added `Symfony\Component\Mercure\Jwt\TokenFactoryInterface`
* added `Symfony\Component\Mercure\Jwt\StaticTokenProvider`
* added `Symfony\Component\Mercure\Jwt\CallabkeTokenProvider`
* added `Symfony\Component\Mercure\Jwt\LcobucciTokenFactory`
* added `Symfony\Component\Mercure\Jwt\FactoryTokenProvider`
* added `Symfony\Component\Mercure\Messenger\UpdateHandler`
* added `Symfony\Component\Mercure\Hub`
* added `Symfony\Component\Mercure\HubInterface`
* added `Symfony\Component\Mercure\HubRegistry`
* added `Symfony\Component\Mercure\Discovery`
* added `Symfony\Component\Mercure\Authorization`
* deprecated `Jwt\StaticJwtProvider`, use `Jwt\StaticTokenProvider` instead.
* deprecated `PublisherInterface` interface in favor of `HubInterface`.
* deprecated `Publisher` class in favor of `Hub`.
* deprecated `Debug\TraceablePublisher` class in favor of `Debug\TraceableHub`.

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
