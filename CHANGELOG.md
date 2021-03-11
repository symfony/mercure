CHANGELOG
=========

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
* deprecated `Jwt\StaticJwtProvider`, use `Jwt\StaticTokenProvider` instead.
* deprecated passing a url and a callable jwt provider to `Publisher::__construct`, pass a `Hub` instance instead.
* deprecated `PublisherInterface::__invoke` method in favor of `PublisherInterface::publish`.

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
