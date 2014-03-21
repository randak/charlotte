Charlotte
=========
Author: Kristian Randall <kristian.l.randall@gmail.com>
Copyright 2014

PHP-based web crawler for site analysis. Crawls your website and stores information about your 
pages, scripts and stylesheets in a graph database. (Can be extended to use any database.)


Usage
------
Open the file `crawl.php` and set the database host and port and the URL.

Charlotte is currently designed to be run from CLI only. To use:

> php crawl.php

```php
	<?php

	require('vendor/autoload.php');

	use Everyman\Neo4j\Client;
	use Charlotte\Charlotte;
	use Charlotte\Processor\Neo4jProcessor;

	$client = new Client("localhost", 7474);

	$charlotte = new Charlotte();

	$charlotte->setStart("http://www.example.com");
	$charlotte->setProcessor(new Neo4jProcessor($client));

	$charlotte->traverse();
```

Contributions
-------------
Contributions are welcome! If you'd like to contribute, please create an issue first.