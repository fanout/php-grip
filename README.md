# GRIP Library for PHP

A GRIP interface library for PHP.  For use with HTTP reverse proxy servers
that support the GRIP interface, such as Pushpin.

Supported GRIP servers include:

* [Pushpin](http://pushpin.org/)
* [Fastly Fanout](https://docs.fastly.com/products/fanout)

This library also supports legacy services hosted by [Fanout](https://fanout.io/) Cloud.

Authors: Katsuyuki Omuro <komuro@fastly.com>, Madeline Boby <maddie.boby@fastly.com>

## New

- Support for `verify_iss` and `verify_key` GRIP configurations and parsing them from GRIP_URLs.
- Support for Bearer tokens, using the new `Fanout\Grip\Auth\BearerAuth` class.
  - Use a Bearer token by using a GRIP configuration with `key`, but without a `control_iss`. This can also be parsed
    from `GRIP_URL` that have a `key` without an `iss`.
- Updated with full support for Fastly Fanout.

## Requirements

`php-grip` uses Guzzle 7 to make HTTP requests, so it has the same requirements as
Guzzle.

1. PHP 7.2.5
2. You must have a recent version of cURL >= 7.19.4 compiled with OpenSSL and zlib.

## Installation

At the current moment the only supported installation uses Composer.

```
    composer install fanout/grip
```

## Past versions

This is a replacement for `fanout/php-pubcontrol` and `fanout/php-gripcontrol`.
This library no longer uses pthreads for managing asynchronous requests.

## Example

For an HTTP Stream publishing example,
see README.md in the `examples/http-stream` directory.

For a WS-over-HTTP example,
see README.md in the `examples/ws-over-http` directory.

## Testing

Run tests using the following command:

```
./vendor/bin/phpunit
```
