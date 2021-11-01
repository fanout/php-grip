## PSR-15 Middleware HTTP Publish Example

This example uses the GripMiddleware class.

Check out this project and run composer install.
```
% composer install
```

Set up Pushpin with a route:
```
* localhost:3000
```

Run Pushpin
```
% pushpin
```

In another Terminal window, start the example index file
```
% php -S 0.0.0.0:3000 -t examples/psr15/slim/http-stream
```
NOTE: In my experience, this fails when I use localhost instead of `0.0.0.0` here.

Hit the endpoint with curl
```
% curl -i http://localhost:7999/api/stream
```

You should see:
```
% curl -i http://localhost:7999/api/stream
HTTP/1.1 200 OK
Host: localhost:7999
Date: Wed, 21 Jul 2021 06:33:08 GMT
X-Powered-By: PHP/8.0.8
Content-type: text/html; charset=UTF-8
Transfer-Encoding: chunked
Connection: Transfer-Encoding

[open stream]
```

Now, in a new Terminal window:
```
% curl -i -d 'Message' http://localhost:7999/api/publish
```

You should see:
```
% curl -i -d 'Message' http://localhost:7999/api/publish
HTTP/1.1 200 OK
Host: localhost:7999
Date: Mon, 01 Nov 2021 07:15:53 GMT
X-Powered-By: PHP/8.0.12
Content-type: text/plain;charset=UTF-8
Content-Length: 3

Ok
```

In the other window, you should now see:
```
Message
```
