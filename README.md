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
- Also provided as a PSR-15-compatible middleware.

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

## API

The API exports the following classes.

| Class | Description |
| --- | --- |
| `Fanout\Grip\Data\GripInstruct` | Class used to create the necessary HTTP headers that instruct the GRIP proxy to hold connections. |
| `Fanout\Grip\Engine\Publisher` | Main object used to publish HTTP response and HTTP Stream format messages to GRIP proxies. |
| `Fanout\Grip\Data\Http\HttpStreamFormat` | Format used to publish messages to HTTP stream clients connected to a GRIP proxy. |
| `Fanout\Grip\Data\Http\HttpResponseFormat` | Format used to publish messages to HTTP response clients connected to a GRIP proxy. |
| `Fanout\Grip\Data\WebSockets\WebSocketContext` | WebSocket context |
| `Fanout\Grip\Data\WebSockets\WebSocketEvent` | WebSocket event |
| `Fanout\Grip\Data\WebSockets\WebSocketMessageFormat` | Format used to publish messages to Web Socket clients connected to a GRIP proxy. |
| `Fanout\Grip\Auth\JwtAuth` | Utility class to help authenticate JWTs (JSON Web Tokens) |

Class `GripInstruct`

| Method | Description |
| --- | --- |
| constructor(`channels?`) | Create a `GripInstruct` instance, configuring it with an optional array of channels to bind to. |
| `add_channel(channels)` | Bind to additional channels. |
| `set_hold_long_poll(timeout?)` | Set the `Grip-Hold` header to the `response` value, and specify an optional timeout value. |
| `set_hold_stream()` | Set the `Grip-Hold` header to the `stream` mode. |
| `set_keep_alive(data, timeout)` | Set the `Grip-Keep-Alive` header to the specified data value and timeout value. The value for `data` may be provided as either a string or `Buffer`, and the appropriate encoding will be performed. |
| `set_next_link(uri, timeout?)` | Set the `Grip-Link` header to the specified uri, with an optional timeout value. |
| `meta` (property) | A property to be set directly on the instance. This is serialized into the `Grip-Set-Meta` header. |
| `build_headers(params)` | Turns the current instance into an object that can be sent as HTTP headers. |

Class `Publisher`

| Method | Description |
| --- | --- |
| constructor(`configs`) | Create a `Publisher` instance, configuring it with clients that based on the specified GRIP settings. |
| `apply_config(configs)` | Apply additional clients based on specified GRIP configs to the publisher instance. |
| `publish(channel, item)` | Publish an item to the specified channel. Returns a Guzzle PromiseInterface. |
| `publish_formats(channel, formats, id?, prev_id?)` | Build an item from the provided formats, id, and previous id, and publish an item to the specified channel. Returns a Guzzle PromiseInterface. |
| `publish_http_response(channel, data, id?, prev_id?)` | Publish an HTTP response format message to the specified channel, with optional ID and previous ID. Returns a Guzzle PromiseInterface. |
| `publish_http_stream(channel, item)` | Publish an HTTP stream format message to the specified channel, with optional ID and previous ID. Returns a Guzzle PromiseInterface. |
| `publish_websocket_message(channel, item)` | Publish a WebSocket format message to the specified channel, with optional ID and previous ID. Returns a Guzzle PromiseInterface. |
| `add_client(client)` | Advanced: Add a PublisherClient instance that you have configured on your own. |

Miscellaneous utility functions:

| Function | Description |
| --- | --- |
| `Fanout\Grip\Data\WebSockets\WebSocketContext::create_web_socket_control_message(type, args)` | Generate a WebSocket control message with the specified type and optional arguments. |
| `Fanout\Grip\Data\WebSockets\WebSocketEvent::encode_events(events)` | Encode the specified array of WebSocketEvent instances. |
| `Fanout\Grip\Data\WebSockets\WebSocketEvent::decode_events(body)` | Decode the specified HTTP request body into an array of WebSocketEvent instances when using the WebSocket-over-HTTP protocol. |
| `Fanout\Grip\Utils\GripUriUtil::parse(uri)` | Parse the specified GRIP URI into a config object that can then be used to construct a `Publisher` instance. |
| `Fanout\Grip\Auth\JwtAuth::validate_signature(token, key)` | Validate the specified JWT token and key. |

## PSR-15 Middleware

`php-grip` includes a PSR-15-compatible Middleware class that can be used
with frameworks such as Slim.

To use this Middleware, instantiate an instance of `GripMiddleware` and add it
to your app according to the framework.  When instantiating this object,
pass an object that contains the options.

For example, using Slim:
```php
$app = AppFactory::create();

$middleware = new GripMiddleware([
    'grip' => 'http://localhost:5561/'
]);
$app->add( $middleware );
```

Available options:
| Key | Value |
| --- | --- |
| `grip` | A definition of GRIP proxies used to publish messages. See below for details. |
| `prefix` | An optional string that will be prepended to the name of channels being published to. This can be used for namespacing. Defaults to `''`. |
| `grip_proxy_required` | A boolean value representing whether all incoming requests should require that they be called behind a GRIP proxy.  If this is true and a GRIP proxy is not detected, then a `501 Not Implemented` error will be issued. Defaults to `false`. |

The `grip` parameter may be provided as any of the following:

1. An object with the following fields:

| Key | Value |
| --- | --- |
| `control_uri` | Publishing endpoint for the GRIP proxy. |
| `control_iss` | A claim string that is needed for servers that require authorization. For Fanout Cloud, this is the Realm ID. |
| `key` | A key string that is needed for servers that require authorization. For Fanout Cloud, this is the Realm Key. |

2. An array of such objects.

3. A GRIP URI, which is a string that encodes the above as a single string.

Once the middleware is installed, it will be available to applicable routes.

In any route, it will be possible to obtain the `GripContext`:

```php
    $grip = GripMiddleware::get_grip_context( $request );
```

The `GripContext` object provides access to the following functions:

| Key | Description |
| --- | --- |
| `$grip->is_proxied()` | A boolean value indicating whether the current request has been called via a GRIP proxy. |
| `$grip->is_signed()` | A boolean value indicating whether the current request is a signed request called via a GRIP proxy. |
| `$grip->start_instruct()` | Returns an instance of `GripInstruct`, which is used to provide `Grip-Instruct` headers to the GRIP proxy. |
| `$grip->get_ws_context()` | Returns an instance of `WebSocketContext`, which is used in WS-over-HTTP. |

To publish messages, call `$grip_middleware->get_publisher()` to obtain an instance of
`Publisher`.

## Example

For an HTTP Stream publishing example,
see README.md in the `examples/http-stream` directory.

For a WS-over-HTTP example,
see README.md in the `examples/ws-over-http` directory.

For an example of using the PSR-15 Middleware,
see README.md in the `slim/http-stream` and `slim/ws-over-http` directories.
