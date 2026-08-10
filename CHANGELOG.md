CHANGELOG
=========

0.8.0
-----

* Add support for the Mercure protocol 1.0, alongside the existing 0.x protocol (`Symfony\Component\Mercure\ProtocolVersion`, opt-in per hub, `0.x` stays the default until Mercure hub 1.0 is tagged stable)
* Add `HubInterface::getProtocolVersion()` and `HubInterface::getCookieName()`
* Add `Hub`/`FrankenPhpHub`/`MockHub` constructor parameters `$cookieName` and `$protocolVersion`
* Change the default subscriber authorization cookie name to `__Secure-mercure_access_token` when a hub is configured for protocol 1.0 (`mercureAuthorization` stays the default for 0.x); the `__Secure-`/`__Host-` prefix contract itself is enforced by `Symfony\Component\HttpFoundation\Cookie` (symfony/symfony#65162), not by this package
* Add Mercure protocol 1.0 support (the `authorization_details` claim, an RFC 9068 access token, `at+jwt`) to `Symfony\Component\Mercure\Jwt\LcobucciFactory`, selected via its new `$protocolVersion` constructor parameter
* Allow `TokenFactoryInterface::create()`'s `$subscribe`/`$publish` parameters, and the Twig `mercure()` function's `$topics` parameter and `subscribe`/`publish` options, to be an associative array mapping a topic matcher type (`exact`, `urlpattern`, or a registered extension type) to a list of patterns, in addition to the existing flat topic list
* Add `FactoryTokenProvider`'s `$additionalClaims` constructor parameter, forwarded to the wrapped factory
* Add `Symfony\Component\Mercure\Jwt\DefaultClaimsTokenFactory`, a `TokenFactoryInterface` decorator merging in a fixed set of claims (e.g. a hub's `iss`/`aud`/`sub`/`client_id`) so `Authorization` and the Twig `mercure()` function, which call `HubInterface::getFactory()` directly, get them without repeating them on every call
* Add `Symfony\Component\Mercure\Jwt\Grant` (`actions`/`topics`/`payload`), replacing `TokenFactoryInterface::create()`'s `$subscribe`/`$publish` parameters and the `additionalClaims['mercure']['payload']` bag key; a single `Grant` can now carry both `subscribe` and `publish` actions over the same topics, producing one `authorization_details` entry instead of two, and a `payload` is validated (requires a topic, only meaningful with the `subscribe` action) instead of silently colliding with the legacy `mercure` claim's own use of the same key
* Model `Authorization::createCookie()`/`setCookie()` directly on `Grant`: `$subscribe` is renamed `$grants` and now also accepts a `Grant[]` list or a bare topic string, in addition to its previous flat-topic-list/matcher-map shapes (`$payload` folds into `Grant`'s own constructor and is dropped as a separate parameter); `$publish` is deprecated in favor of adding a `Grant::ACTION_PUBLISH` Grant to `$grants`, and passing `null` for `$grants` is deprecated in favor of `[]`. The `$subscribe` → `$grants` rename breaks named-argument calls using `subscribe:` specifically — positional calls and `publish:`/`additionalClaims:`/`hub:` named args are unaffected. The Twig `mercure()` function gains a matching `grants` option; its existing `subscribe`/`publish`/`payload` options are unaffected (translated internally the same way)
* Add `MatcherInput::normalizeGrants()`, the shared logic now backing both `Authorization::$grants` and the Twig `grants` option; besides `Grant[]` and the topic shorthands, it also accepts a list of Grant-shaped associative arrays (`actions`/`topics`/`payload`), letting contexts that can't construct a `Grant` object directly (e.g. a Twig template) still express multi-action or payload-bearing grants
* `Symfony\Component\Mercure\Jwt\LcobucciFactory` no longer forces integer `exp`/`iat`/`nbf` under protocol 1.0; a resource server is expected to accept RFC 9068's `NumericDate` as either an integer or a float carrying sub-second precision, `lcobucci/jwt`'s own default

0.7.2
-----

* Revert lazy-loading the Twig extension to fix compatibility with Symfony 6.4

0.7.1
-----

* Compatibility with PHP 8.5

0.7.0
-----

* Add support for FrankenPHP's `mercure_publish()` function
* Compatibility with Symfony 8
* Lazy-load the Twig extension
* Drop support for unmaintained PHP and Symfony versions 

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
