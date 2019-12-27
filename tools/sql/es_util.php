<?php

use Elasticsearch\ClientBuilder;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/5.0/_configuration.html
function get_es_connection() {
    global $es_host, $es_port, $es_user, $es_pass;

	$hosts = [
    	[
        	'host' => $es_host,
        	'port' => $es_port,
        	'scheme' => 'http',
        	'user' => $es_user,
        	'pass' => $es_pass
    	]
	];
	$client = Elasticsearch\ClientBuilder::create()
    	->setHosts($hosts)
    	->setRetries(0)
    	->build();

	return $client;
}

