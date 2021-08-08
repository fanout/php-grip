## WebSocket-over-HTTP Example

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
% php -S 0.0.0.0:3000 -t examples/ws-over-http
```
NOTE: In my experience, this fails when I use localhost instead of `0.0.0.0` here.

In yet another Terminal window, issue a WebSocket request.
```
wscat --connect ws://localhost:7999/
```

You should see a prompt where you may enter a message.  This application acts as an
echo server, and any text you enter will be repeated back to you. 

Finally, in another Terminal window, post a message.
```
% php examples/ws-over-http/broadcast.php Message
Publish URI: http://localhost:5561/
Channel: test
Message: Message
Publish Successful!
```

In the other window, you will see the message appear. 
