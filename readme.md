Charlotte
=========
Author: Kristian Randall <kristian.l.randall@gmail.com>
Copyright 2014

PHP-based web crawler for site analysis. Crawls your website and stores information about your
pages, scripts and stylesheets in a Neo4j graph database. (Can be extended to use any database.)

Installation
------------
Install using [Composer](https://getcomposer.org/):

> composer require randak/charlotte:dev-master

Depending on your composer settings, you may need to run `composer require everyman/neo4jphp:dev-master`
before you can install Charlotte. If you get an error about that package not being
available, this is the likely solution.

In addition to installing Charlotte, you'll also need Neo4j, whether it be on the
same machine or another server.

Configuration
-------------
After installation, you will need to set up your configuration. Currently, there
is an example config file in the `examples` folder. The config will look something
like this:

```yaml
    crawler:
        start: http://www.example.com
        exclude:
            - "/^javascript\:void\(0\)$/"
            - "/^#.*/"
            - "/^\\/$/"
            - "/\.(pdf|zip|zi|png|jpg|jpeg|doc|ppt)$/i"
    connections:
        Neo4j:
            host: localhost
            port: 7474
```

You should set the URL here to be the homepage of the website you wish to crawl.

The exclude patterns are regular expressions that will match URLs you don't want
to crawl. For example, we are ignoring certain file types, and any URL that starts
with a #.

Usage
------
Charlotte is currently designed to be run from the command line only.

Create a file called `crawl.php`.

> touch crawl.php

Insert the follow.

```php
    <?php

    require('path/to/vendor/autoload.php'); //set this

    use Everyman\Neo4j\Client;
    use Charlotte\Charlotte;
    use Charlotte\Processor\Neo4jProcessor;
    use Symfony\Component\Yaml\Parser;

    $yaml = new Parser();
    $config = $yaml->parse(file_get_contents(__DIR__."/config.yml")); //set this

    $conn = $config["connections"]["Neo4j"];

    $client = new Client($conn["host"], $conn["port"]);

    $charlotte = new Charlotte();

    $charlotte->setConfig($config);
    $charlotte->setProcessor(new Neo4jProcessor($client));

    $charlotte->traverse();
```

Make sure everything is set up in your `config.yml` and that your database is open.

Run the script.

> php crawl.php

Contributions
-------------
Contributions are welcome! If you'd like to contribute, please create an issue first.
