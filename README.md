Phpcent
========

Php library to communicate with Centrifugo HTTP API.

Library is published on the Composer: https://packagist.org/packages/sl4mmer/phpcent
```php
{
    "require": {
        "hetao29/phpcent2":"dev-master",
    }
}
```

Full [Centrifugo documentation](https://fzambia.gitbooks.io/centrifugal/content/)

Basic Usage:

```php
        
		//to backend api
        $client = new \phpcent2\Client("http://localhost:8000");
        $client->setApikey("api key from Centrifugo");//to backend api
        $client->publish("main_feed", ["message" => "Hello Everybody"]);
        $history = $client->history("main_feed");

		//to get token
        $client->setSecret("secret key from Centrifugo");
        $token = $client->getToken([
				"data"=>[],
				"sub"=>1
		]);
        
```

You can use `phpcent2` to create frontend token:

```php
	$token = $client->setSecret($pSecret)->getToken($data);
```

### SSL

In case if your Centrifugo server has invalid SSL certificate, you can use:

```php
\phpcent2\Transport::setSafety(\phpcent\Transport::UNSAFE);
```

Since 1.0.5 you can use self signed certificate in safe manner:

```php
$client = new \phpcent2\Client("https://localhost:8000");
$client->setSecret("secret key from Centrifugo");
$transport = new \phpcent2\Transport();
$transport->setCert("/path/to/certificate.pem");
$client->setTransport($transport);
```

*Note:* Certificate must match with host name in `Client` address (`localhost` in example above).

Alternative clients
===================

* [php-centrifugo](https://github.com/oleh-ozimok/php-centrifugo) - allows to work with Redis Engine API queue.
* [php_cent](https://github.com/skoniks/php_cent) by [skoniks](https://github.com/skoniks)

