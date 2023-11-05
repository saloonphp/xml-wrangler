<?php

declare(strict_types=1);

use Saloon\XmlWrangler\Data\RootElement;
use Saloon\XmlWrangler\Traits\HasAttributes;

test('uses has attributes')
    ->expect(RootElement::class)
    ->toUse(HasAttributes::class);

test('you can set the name of the root element', function () {
    $rootElement = new RootElement('root');

    expect($rootElement->getName())->toBe('root');

    $rootElement->setName('sam');

    expect($rootElement->getName())->toBe('sam');
});

test('you can get the name prefix from the root element', function (string $name, ?string $prefix) {
    $rootElement = new RootElement($name);

    expect($rootElement->getNamePrefix())->toEqual($prefix);
})->with([
    ['root', null],
    ['soap:root', 'soap'],
]);

test('you can get the name prefix from the root element with glue', function (string $name, ?string $prefix) {
    $rootElement = new RootElement($name);

    expect($rootElement->getNamePrefix(true))->toEqual($prefix);
})->with([
    ['root', null],
    ['soap:root', 'soap:'],
]);
