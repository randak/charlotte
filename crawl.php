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