## PSR-15 Middleware HTTP Publish Example

This example uses the GripMiddleware class.

This example uses `wscat` (a node application) to test WebSockets connections.
```
npm install -g wscat
```

Check out this project and run composer install.
```
% composer install
```

Set up Pushpin with a route:
```
* localhost:3000,over_http
```

Run Pushpin
```
% pushpin
```

In another Terminal window, start the example index file
```
% php -S 0.0.0.0:3000 -t examples/psr15/slim/ws-over-http
```
NOTE: In my experience, this fails when I use localhost instead of `0.0.0.0` here.

In yet another Terminal window, issue a WebSocket request.
```
wscat --connect ws://localhost:7999/api/websocket
```

You should see a prompt where you may enter a message.  This application acts as an
echo server, and any text you enter will be repeated back to you.

Finally, in another Terminal window, post a message.
```
% curl -i -d 'Message' http://localhost:7999/api/broadcast
```

You should see:
```
% curl -i -d 'Message' http://localhost:7999/api/broadcast
HTTP/1.1 200 OK
Host: localhost:7999
Date: Mon, 01 Nov 2021 07:34:07 GMT
X-Powered-By: PHP/8.0.12
Content-type: text/plain;charset=UTF-8
Content-Length: 3

Ok
```

In the other window, you will see the message appear. 
