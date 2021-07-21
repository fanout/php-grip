## HTTP Publish Example

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

Start the example index file
```
% php -S 0.0.0.0:3000 -t examples/http-stream
```
NOTE: In my experience, this fails when I use localhost instead of `0.0.0.0` here.

Hit the endpoint with curl
```
% curl -i http://localhost:7999/
```

You should see:
```
% curl -i http://localhost:7999
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
% php examples/http-stream/publish.php test Message
Publish URI: http://localhost:5561/
Channel: test
Message: Message
Publish Successful!
```

In the other window, you should now see:
```
Message
```
