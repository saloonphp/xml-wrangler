<?php

declare(strict_types=1);

use Saloon\XmlWrangler\Query;
use Saloon\XmlWrangler\LazyQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Saloon\XmlWrangler\Exceptions\MissingNodeException;
use Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException;
use Saloon\XmlWrangler\Exceptions\MultipleNodesFoundException;

test('can view all values in a node', function () {
    $node = new Query('test', multiValueGenerator());

    expect($node->get())->toEqual(['a', 'b', 'c', 'd', 'e']);
});

test('can get all values as a collection', function () {
    $node = new Query('test', multiValueGenerator());

    $collection = $node->collect();

    expect($collection)->toBeInstanceOf(Collection::class);

    expect($collection->toArray())->toEqual(['a', 'b', 'c', 'd', 'e']);
});

test('can iterate through values lazily', function () {
    $node = new LazyQuery('test', multiValueGenerator());

    $lazy = $node->lazy();

    expect($lazy)->toBeInstanceOf(Generator::class);

    expect(iterator_to_array($lazy))->toEqual(['a', 'b', 'c', 'd', 'e']);
});

test('can iterate through values lazily with a collection', function () {
    $node = new LazyQuery('test', multiValueGenerator());

    $lazy = $node->collectLazy();

    expect($lazy)->toBeInstanceOf(LazyCollection::class);

    expect($lazy->toArray())->toEqual(['a', 'b', 'c', 'd', 'e']);
});

test('can get the first item', function () {
    $node = new Query('test', multiValueGenerator());

    expect($node->first())->toEqual('a');
});

test('can return null if the collection is missing', function () {
    $node = new Query('test', emptyGenerator());

    expect($node->first())->toBeNull();
});

test('can get the first item or fail', function () {
    $node = new Query('test', emptyGenerator());

    $this->expectException(MissingNodeException::class);
    $this->expectExceptionMessage('Unable to find the [test] node');

    $node->firstOrFail();
});

test('can get the first item or fail using sole', function () {
    $node = new Query('test', emptyGenerator());

    $this->expectException(MissingNodeException::class);
    $this->expectExceptionMessage('Unable to find the [test] node');

    $node->sole();
});

test('sole throws an exception if there are multiple items', function () {
    $node = new Query('test', multiValueGenerator());

    $this->expectException(MultipleNodesFoundException::class);
    $this->expectExceptionMessage('Multiple nodes found for [test]');

    $node->sole();
});

test('it will throw an exception if you attempt to use one of the methods on the query after is is read', function (string $method) {
    $node = new LazyQuery('test', multiValueGenerator());

    $node->get();

    $this->expectException(QueryAlreadyReadException::class);
    $this->expectExceptionMessage('The underlying generator on this query instance has already been used.');

    $node->$method();
})->with([
    'lazy', 'collectLazy', 'get', 'collect', 'first', 'firstOrFail', 'sole',
]);
