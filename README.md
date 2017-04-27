# PHP Solarium Guzzle Promise Plugin

The purpose of this package is to make a plugin available for the PHP Solarium ([solarium/solarium](https://packagist.org/packages/solarium/solarium)) [Solr](http://lucene.apache.org/solr/) client library that uses a [Guzzle 6](http://docs.guzzlephp.org/en/latest/) adapter to allow asynchronous fetches from Solr using the [Guzzle Promises/A+ implementation](https://github.com/guzzle/promises), returning promises from the plugin that can evaluated later, or in parallel with other promises.

In simpler cases where small numbers of parallel fetches are needed it would likely be more straightforward to use Solarium's built-in [ParallelExecution plugin](http://solarium.readthedocs.io/en/stable/plugins/#parallelexecution-plugin). However, Guzzle 6 has better support for heavier/ more complex cases (that might otherwise max out available sockets etc), and can be then used for parallel execution of other kinds of fetches.

## Example usage

```php
<?php

//create a client instance with a previously created config, register and get the plugin
$client = new Solarium\Client($config);
$plugin = $client
    ->registerPlugin('guzzlePromise', new Shieldo\GuzzlePromisePlugin\GuzzlePromisePlugin())
    ->getPlugin('guzzlePromise');

// create two queries
$queryInstock = $client->createSelect()->setQuery('inStock:true');
$queryLowprice = $client->createSelect()->setQuery('price:[1 TO 300]');

// option 1: fetch promises out of the plugin for separate execution
$promises = [
    'in_stock' => $plugin->queryAsync($queryInstock),
    'low_price' => $plugin->queryAsync($queryLowprice),
];
$results = GuzzleHttp\Promise\unwrap($promises);

// option 2: use inbuilt promise execution, with theoretical max of 6 open sockets at one time

$results = $plugin->queryParallel([
    'in_stock' => $queryInstock,
    'low_price' => $queryLowprice,
], 6);
```
