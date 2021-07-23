# Grip Library for PHP

This is a replacement for fanout/php-pubcontrol and fanout/php-gripcontrol.

Requirements:

php-grip uses Guzzle 7 to make HTTP requests, so it has the same requirements as
Guzzle.

1. PHP 7.2.5
2. You must have a recent version of cURL >= 7.19.4 compiled with OpenSSL and zlib.

Installation:

At the current moment the only supported installation uses Composer.

```
    composer install fanout/grip
```

This version does not yet include all of the features for WebSocket-over-HTTP.

## Example

For an HTTP Stream publishing example,
see README.md in the `examples/http-stream` directory.
