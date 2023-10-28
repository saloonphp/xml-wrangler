<?php

declare(strict_types=1);

use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;
use Saloon\XmlWrangler\XmlReader;

test('can parse xml and convert it into an array of elements', function () {
    $file = file_get_contents('tests/Fixtures/breakfast-menu.xml');

    $reader = XmlReader::fromString($file);

    $belgianWaffles = Element::make([
        'name' => Element::make('Belgian Waffles'),
        'price' => Element::make('$5.95'),
        'description' => Element::make('Two of our famous Belgian Waffles with plenty of real maple syrup'),
        'calories' => Element::make('650'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'true',
    ]);

    $strawberryBelgianWaffles = Element::make([
        'name' => Element::make('Strawberry Belgian Waffles'),
        'price' => Element::make('$7.95'),
        'description' => Element::make('Light Belgian waffles covered with strawberries and whipped cream'),
        'calories' => Element::make('900'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'false',
    ]);

    $berryberryBelgianWaffles = Element::make([
        'name' => Element::make('Berry-Berry Belgian Waffles'),
        'price' => Element::make('$8.95'),
        'description' => Element::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
        'calories' => Element::make('900'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'true',
    ]);

    $frenchToast = Element::make([
        'name' => Element::make('French Toast'),
        'price' => Element::make('$4.50'),
        'description' => Element::make('Thick slices made from our homemade sourdough bread'),
        'calories' => Element::make('600'),
    ])->setAttributes([
        'soldOut' => 'true', 'bestSeller' => 'false',
    ]);

    $homestyleBreakfast = Element::make([
        'name' => Element::make('Homestyle Breakfast'),
        'price' => Element::make('$6.95'),
        'description' => Element::make('Two eggs, bacon or sausage, toast, and our ever-popular hash browns'),
        'calories' => Element::make('950'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'false',
    ]);

    expect($reader->elements())->toEqual([
        'breakfast_menu' => Element::make([
            'food' => new Element([
                $belgianWaffles,
                $strawberryBelgianWaffles,
                $berryberryBelgianWaffles,
                $frenchToast,
                $homestyleBreakfast,
            ])
        ])->addAttribute('name', 'Big G\'s Breakfasts')
    ]);
});

test('can parse xml and convert it into an array of values', function () {
    $file = file_get_contents('tests/Fixtures/breakfast-menu.xml');

    $reader = XmlReader::fromString($file);

    expect($reader->values())->toEqual([
        'breakfast_menu' => [
            'food' => [
                [
                    'name' => 'Belgian Waffles',
                    'price' => '$5.95',
                    'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
                    'calories' => '650',
                ],
                [
                    'name' => 'Strawberry Belgian Waffles',
                    'price' => '$7.95',
                    'description' => 'Light Belgian waffles covered with strawberries and whipped cream',
                    'calories' => '900',
                ],
                [
                    'name' => 'Berry-Berry Belgian Waffles',
                    'price' => '$8.95',
                    'description' => 'Light Belgian waffles covered with an assortment of fresh berries and whipped cream',
                    'calories' => '900',
                ],
                [
                    'name' => 'French Toast',
                    'price' => '$4.50',
                    'description' => 'Thick slices made from our homemade sourdough bread',
                    'calories' => '600',
                ],
                [
                    'name' => 'Homestyle Breakfast',
                    'price' => '$6.95',
                    'description' => 'Two eggs, bacon or sausage, toast, and our ever-popular hash browns',
                    'calories' => '950',
                ]
            ]
        ]
    ]);
});

test('can parse xml and search for a specific element', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    $element = $reader->element('customer');

    expect($element)->toBeInstanceOf(Element::class);
    expect($element->getAttributes())->toEqual(['id' => '55000']);
    expect($element->getContent())->toBeArray();
    expect($element->getContent())->toHaveCount(2);
});

test('can parse xml and search for a specific value', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    $value = $reader->value('customer');

    expect($value)->toBeArray();
    expect($value)->toHaveCount(2);
    expect($value)->toHaveKeys(['name', 'address']);
});

test('it throws an exception if an element could not be found', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    expect($reader->element('person', [], true))->toBeNull();

    $this->expectException(XmlReaderException::class);
    $this->expectExceptionMessage('Unable to find [person] element');

    $reader->element('person');
});

test('it throws an exception if a value could not be found', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    expect($reader->value('person', [], true))->toBeNull();

    $this->expectException(XmlReaderException::class);
    $this->expectExceptionMessage('Unable to find [person] element');

    $reader->value('person');
});

test('can use dot notation to find a specific nested element', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    $value = $reader->value('customers.customer.name');

    expect($value)->toBe('Charter Group');
});

test('when the elements have multiple an array is returned', function () {
    $file = file_get_contents('tests/Fixtures/breakfast-menu.xml');

    $reader = XmlReader::fromString($file);

    $food = $reader->value('breakfast_menu.food');

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);
    expect($food)->toEqual([
        [
            'name' => 'Belgian Waffles',
            'price' => '$5.95',
            'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
            'calories' => '650',
        ],
        [
            'name' => 'Strawberry Belgian Waffles',
            'price' => '$7.95',
            'description' => 'Light Belgian waffles covered with strawberries and whipped cream',
            'calories' => '900',
        ],
        [
            'name' => 'Berry-Berry Belgian Waffles',
            'price' => '$8.95',
            'description' => 'Light Belgian waffles covered with an assortment of fresh berries and whipped cream',
            'calories' => '900',
        ],
        [
            'name' => 'French Toast',
            'price' => '$4.50',
            'description' => 'Thick slices made from our homemade sourdough bread',
            'calories' => '600',
        ],
        [
            'name' => 'Homestyle Breakfast',
            'price' => '$6.95',
            'description' => 'Two eggs, bacon or sausage, toast, and our ever-popular hash browns',
            'calories' => '950',
        ]
    ]);
});

test('can use numbers to find a specific index of a nested element with dot notation', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $berryBerryBelgianWaffles = $reader->value('breakfast_menu.food.2');

    expect($berryBerryBelgianWaffles)->toEqual([
        'name' => 'Berry-Berry Belgian Waffles',
        'price' => '$8.95',
        'description' => 'Light Belgian waffles covered with an assortment of fresh berries and whipped cream',
        'calories' => '900',
    ]);

    $name = $reader->value('food.2.name');

    expect($name)->toEqual('Berry-Berry Belgian Waffles');
});

test('can search for an element and it will return every value', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $names = $reader->value('name');

    expect($names)->toEqual([
        'Belgian Waffles',
        'Strawberry Belgian Waffles',
        'Berry-Berry Belgian Waffles',
        'French Toast',
        'Homestyle Breakfast',
    ]);

    $name = $reader->value('name.2');

    expect($name)->toEqual('Berry-Berry Belgian Waffles');
});

test('when using dot notation it will throw exceptions if a value could not be found', function (string $search, string $message) {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $this->expectException(XmlReaderException::class);
    $this->expectExceptionMessage('Unable to find [' . $message . '] element');

    $reader->element($search);
})->with([
    ['food.6', 'food'], ['1.1', '1'],
]);

test('can search for a nested element with specific attributes', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $soldOut = $reader->element('breakfast_menu.food', ['soldOut' => 'true']);

    expect($soldOut)->toBeInstanceOf(Element::class);

    expect($soldOut)->toEqual(
        Element::make()->setAttributes(['soldOut' => 'true', 'bestSeller' => 'false'])->setContent([
            'name' => Element::make('French Toast'),
            'price' => Element::make('$4.50'),
            'description' => Element::make('Thick slices made from our homemade sourdough bread'),
            'calories' => Element::make('600'),
        ]),
    );

    $bestSellers = $reader->element('breakfast_menu.food', ['bestSeller' => 'true']);

    expect($bestSellers)->toBeArray();

    expect($bestSellers)->toEqual([
        Element::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => Element::make('Belgian Waffles'),
            'price' => Element::make('$5.95'),
            'description' => Element::make('Two of our famous Belgian Waffles with plenty of real maple syrup'),
            'calories' => Element::make('650'),
        ]),
        Element::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => Element::make('Berry-Berry Belgian Waffles'),
            'price' => Element::make('$8.95'),
            'description' => Element::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
            'calories' => Element::make('900'),
        ]),
    ]);

    $notSoldOutNotBestSeller = $reader->element('breakfast_menu.food', ['soldOut' => 'false', 'bestSeller' => 'false']);

    expect($notSoldOutNotBestSeller)->toEqual([
        Element::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'false'])->setContent([
            'name' => Element::make('Strawberry Belgian Waffles'),
            'price' => Element::make('$7.95'),
            'description' => Element::make('Light Belgian waffles covered with strawberries and whipped cream'),
            'calories' => Element::make('900'),
        ]),
        Element::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'false'])->setContent([
            'name' => Element::make('Homestyle Breakfast'),
            'price' => Element::make('$6.95'),
            'description' => Element::make('Two eggs, bacon or sausage, toast, and our ever-popular hash browns'),
            'calories' => Element::make('950'),
        ]),
    ]);
});

test('can parse xml from a file', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $food = $reader->value('breakfast_menu.food');

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);
});

test('if the file is unreadable it will throw an exception', function () {
    $this->expectException(XmlReaderException::class);
    $this->expectExceptionMessage('Unable to read the [tests/Fixtures/missing.xml] file.');

    XmlReader::fromFile('tests/Fixtures/missing.xml');
});

test('can parse xml from a stream', function () {
    $reader = XmlReader::fromStream(fopen('tests/Fixtures/breakfast-menu.xml', 'rb'));

    $food = $reader->value('breakfast_menu.food');

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);

    // Let's test we can make multiple queries

    $berryBerryWaffles = $reader->value('breakfast_menu.food.2.name');

    expect($berryBerryWaffles)->toEqual('Berry-Berry Belgian Waffles');
});

test('can use xpath to find an element', function () {
    $reader = XmlReader::fromStream(fopen('tests/Fixtures/breakfast-menu.xml', 'rb'));

    $bestSellers = $reader->xpathValue('/breakfast_menu/food[@bestSeller="true"]');

    expect($bestSellers)->toEqual([
        [
            'name' => 'Belgian Waffles',
            'price' => '$5.95',
            'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
            'calories' => '650',
        ],
        [
            'name' => 'Berry-Berry Belgian Waffles',
            'price' => '$8.95',
            'description' => 'Light Belgian waffles covered with an assortment of fresh berries and whipped cream',
            'calories' => '900',
        ],
    ]);

    // Let's test a single value

    $berryBerryWaffles = $reader->xpathElement('/breakfast_menu/food[3]');

    expect($berryBerryWaffles)->toEqual(
        Element::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => Element::make('Berry-Berry Belgian Waffles'),
            'price' => Element::make('$8.95'),
            'description' => Element::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
            'calories' => Element::make('900'),
        ]),
    );
});

test('xpath method will throw an exception if search cannot be found', function () {
    $reader = XmlReader::fromStream(fopen('tests/Fixtures/breakfast-menu.xml', 'rb'));

    $this->expectException(XmlReaderException::class);
    $this->expectExceptionMessage('No results found for [/breakfast_menu/food[@tasty="true"]].');

    $reader->xpathValue('/breakfast_menu/food[@tasty="true"]');
});
