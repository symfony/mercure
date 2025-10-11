Mercure Component
=================

> Mercure is a protocol allowing to push data updates to web browsers and other
  HTTP clients in a convenient, fast, reliable and battery-efficient way.
  It is especially useful to publish real-time updates of resources served through
  web APIs, to reactive web and mobile apps.

The Mercure Component implements the "publisher" part of [the Mercure Protocol](https://mercure.rocks).

Getting Started
---------------

```
$ composer require symfony/mercure lcobucci/jwt
```

```php
// change these values accordingly to your hub installation
const HUB_URL = 'https://demo.mercure.rocks/.well-known/mercure';
const JWT_SECRET = '!ChangeThisMercureHubJWTSecretKey!';

// Set up the JWT token provider
// Alternatively, you can use the \Symfony\Component\Mercure\Jwt\StaticTokenProvider if you already have a JWT token
$jwFactory = new \Symfony\Component\Mercure\Jwt\LcobucciFactory(JWT_SECRET);
$provider = new \Symfony\Component\Mercure\Jwt\FactoryTokenProvider($jwFactory, publish: ['*']);

$hub = new \Symfony\Component\Mercure\Hub(HUB_URL, $provider);
// Serialize the update, and dispatch it to the hub, that will broadcast it to the clients
$id = $hub->publish(new \Symfony\Component\Mercure\Update('https://example.com/books/1.jsonld', 'Hi from Symfony!'));
```

Resources
---------

* [Documentation](https://symfony.com/doc/current/mercure.html)
* [Contributing](https://symfony.com/doc/current/contributing/index.html)
* [Report issues](https://github.com/symfony/mercure/issues) and
  [send Pull Requests](https://github.com/symfony/mercure/pulls)
  in the [`symfony/mercure` repository](https://github.com/symfony/mercure)
