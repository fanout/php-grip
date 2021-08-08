# GRIP Library for PHP

A GRIP interface library for PHP.  For use with HTTP reverse proxy servers
that support the GRIP interface, such as Pushpin.

Supported GRIP servers include:

* [Pushpin](http://pushpin.org/)
* [Fanout Cloud](https://fanout.io/cloud/)

Author: Katsuyuki Ohmuro <kats@fanout.io>

- Major update with great improvements in usability
- Uses Guzzle (and its Promises library) for HTTP fetching and asynchronous functionality.
- Collapsed `php-pubcontrol` and `php-gripcontrol` into a single library,
  simplifying use and deployment.
- Reorganized utility functions into categorized files.
- Install using Composer.  Classes loaded using PSR-4.

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
