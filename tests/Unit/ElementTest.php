<?php

declare(strict_types=1);

use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\Traits\HasAttributes;

test('uses has attributes')
    ->expect(Element::class)
    ->toUse(HasAttributes::class);

test('can get an individual attribute', function () {
    $element = new Element('Howdy!', ['name' => 'Sam', 'twitter' => '@carre_sam']);

    expect($element->getAttribute('name'))->toEqual('Sam');
    expect($element->getAttribute('missing'))->toBeNull();
    expect($element->getAttribute('missing', 'default'))->toBe('default');
});
