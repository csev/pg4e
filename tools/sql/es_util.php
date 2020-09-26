<?php

use Elasticsearch\ClientBuilder;

// https://www.elastic.co/guide/en/elasticsearch/client/php-api/5.0/_configuration.html
function get_es_connection() {
    global $es_host, $es_scheme, $es_port, $es_prefix, $es_user, $es_pass;

    $hosts = [
        [
            // https://github.com/elastic/elasticsearch-php/issues/239
            'host' => $es_host . '/' . $es_prefix,
            'port' => $es_port,
            'scheme' => $es_scheme,
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

function get_es_local() {
    global $CFG;
    if ( ! isset($CFG->elasticsearch_backend) ) return false;
    $pieces = parse_url($CFG->elasticsearch_backend);

    $hosts = [
        [
            'host' => $pieces["host"],
            'scheme' => $pieces['scheme'],
        ]
    ];
    $client = Elasticsearch\ClientBuilder::create()
        ->setHosts($hosts)
        ->setRetries(0)
        ->build();

    return $client;
}

