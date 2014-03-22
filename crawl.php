<?php

require('vendor/autoload.php');

use Everyman\Neo4j\Client;
use Charlotte\Charlotte;
use Charlotte\Processor\Neo4jProcessor;
use Symfony\Component\Yaml\Parser;

$yaml = new Parser();
$config = $yaml->parse(file_get_contents(__DIR__."/config.yml"));

$conn = $config["connections"]["Neo4j"];

$client = new Client($conn["host"], $conn["port"]);

$charlotte = new Charlotte();

$charlotte->setConfig($config);
$charlotte->setProcessor(new Neo4jProcessor($client));

$charlotte->traverse();
