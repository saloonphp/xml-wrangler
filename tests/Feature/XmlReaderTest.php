<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Saloon\XmlWrangler\Query;
use Saloon\XmlWrangler\LazyQuery;
use Saloon\XmlWrangler\XmlReader;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;
use Saloon\XmlWrangler\Tests\Fixtures\Saloon\BreakfastMenuRequest;

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

    $result = [
        'breakfast_menu' => Element::make([
            'food' => new Element([
                $belgianWaffles,
                $strawberryBelgianWaffles,
                $berryberryBelgianWaffles,
                $frenchToast,
                $homestyleBreakfast,
            ]),
        ])->addAttribute('name', 'Big G\'s Breakfasts'),
    ];

    expect($reader->elements())->toEqual($result);
});

test('can parse xml and convert it into an array of values', function () {
    $file = file_get_contents('tests/Fixtures/breakfast-menu.xml');

    $reader = XmlReader::fromString($file);

    $result = [
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
                ],
            ],
        ],
    ];

    expect($reader->values())->toEqual($result);
});

test('can parse xml and search for a specific element', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    $query = $reader->element('customer');

    expect($query)->toBeInstanceOf(LazyQuery::class);

    $element = $query->sole();

    expect($element)->toBeInstanceOf(Element::class);
    expect($element->getAttributes())->toEqual(['id' => '55000']);
    expect($element->getContent())->toBeArray();
    expect($element->getContent())->toHaveCount(2);
});

test('can parse xml and search for a specific value', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    $query = $reader->value('customer');

    expect($query)->toBeInstanceOf(LazyQuery::class);

    $value = $query->sole();

    expect($value)->toBeArray();
    expect($value)->toHaveCount(2);
    expect($value)->toHaveKeys(['name', 'address']);
});

test('can use dot notation to find a specific nested element', function () {
    $file = file_get_contents('tests/Fixtures/customers.xml');

    $reader = XmlReader::fromString($file);

    $value = $reader->value('customer.name')->sole();

    expect($value)->toBe('Charter Group');
});

test('when the elements have multiple an array is returned', function () {
    $file = file_get_contents('tests/Fixtures/breakfast-menu.xml');

    $reader = XmlReader::fromString($file);

    $food = $reader->value('food')->get();

    $results = [
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
        ],
    ];

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);
    expect($food)->toEqual($results);
});

test('can use numbers to find a specific index of a nested element with dot notation', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $berryBerryBelgianWaffles = $reader->value('food.2')->sole();

    expect($berryBerryBelgianWaffles)->toEqual([
        'name' => 'Berry-Berry Belgian Waffles',
        'price' => '$8.95',
        'description' => 'Light Belgian waffles covered with an assortment of fresh berries and whipped cream',
        'calories' => '900',
    ]);

    $name = $reader->value('food.2.name')->sole();

    expect($name)->toEqual('Berry-Berry Belgian Waffles');
});

test('can search for an element and it will return every value', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $names = $reader->value('name')->get();

    expect($names)->toEqual([
        'Belgian Waffles',
        'Strawberry Belgian Waffles',
        'Berry-Berry Belgian Waffles',
        'French Toast',
        'Homestyle Breakfast',
    ]);

    $name = $reader->value('name.2')->sole();

    expect($name)->toEqual('Berry-Berry Belgian Waffles');
});

test('can search for a nested element with specific attributes', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    $soldOut = $reader->element('food', ['soldOut' => 'true'])->sole();

    expect($soldOut)->toBeInstanceOf(Element::class);

    expect($soldOut)->toEqual(
        Element::make()->setAttributes(['soldOut' => 'true', 'bestSeller' => 'false'])->setContent([
            'name' => Element::make('French Toast'),
            'price' => Element::make('$4.50'),
            'description' => Element::make('Thick slices made from our homemade sourdough bread'),
            'calories' => Element::make('600'),
        ]),
    );

    $bestSellers = $reader->element('food', ['bestSeller' => 'true'])->get();

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

    $notSoldOutNotBestSeller = $reader->element('food', ['soldOut' => 'false', 'bestSeller' => 'false'])->get();

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

    $food = $reader->value('food')->get();

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

    $food = $reader->value('food')->get();

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);

    // Let's test we can make multiple queries

    $berryBerryWaffles = $reader->value('food.2.name')->sole();

    expect($berryBerryWaffles)->toEqual('Berry-Berry Belgian Waffles');
});

test('can parse xml from a psr response', function () {
    $guzzle = new Client();

    $response = $guzzle->send(new Request('GET', 'https://tests.saloon.dev/api/breakfast-menu'));

    $reader = XmlReader::fromPsrResponse($response);

    $food = $reader->value('food')->get();

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);

    // Let's test we can make multiple queries

    $berryBerryWaffles = $reader->value('food.2.name')->sole();

    expect($berryBerryWaffles)->toEqual('Berry-Berry Belgian Waffles');
});

test('can parse xml from a saloon response', function () {
    $mockClient = new MockClient([
        MockResponse::fixture('breakfastMenu'),
    ]);

    $response = BreakfastMenuRequest::make()->withMockClient($mockClient)->send();

    $reader = XmlReader::fromSaloonResponse($response);

    $food = $reader->value('food')->get();

    expect($food)->toBeArray();
    expect($food)->toHaveCount(5);

    // Let's test we can make multiple queries

    $berryBerryWaffles = $reader->value('food.2.name')->sole();

    expect($berryBerryWaffles)->toEqual('Berry-Berry Belgian Waffles');
});

test('can use xpath to find an element', function () {
    $reader = XmlReader::fromStream(fopen('tests/Fixtures/breakfast-menu.xml', 'rb'));

    $bestSellers = $reader->xpathValue('/breakfast_menu/food[@bestSeller="true"]');

    expect($bestSellers)->toBeInstanceOf(Query::class);
    expect($bestSellers)->not->toBeInstanceOf(LazyQuery::class);

    expect($bestSellers->get())->toEqual([
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

    expect($berryBerryWaffles)->toBeInstanceOf(Query::class);
    expect($berryBerryWaffles)->not->toBeInstanceOf(LazyQuery::class);

    expect($berryBerryWaffles->sole())->toEqual(
        Element::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => Element::make('Berry-Berry Belgian Waffles'),
            'price' => Element::make('$8.95'),
            'description' => Element::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
            'calories' => Element::make('900'),
        ]),
    );
});

test('you can read cdata', function () {
    $reader = XmlReader::fromString(file_get_contents('tests/Fixtures/customers.xml'));

    $address = $reader->value('customer.address.2')->sole();

    expect($address)->toEqual([
        'street' => '120 Ridge',
        'state' => 'MA',
        'zip' => '01760',
        'notes' => 'This is an example of CDATA content inside of an element. Special characters like % £ are allowed.',
    ]);
});

test('when an element has attributes and text content it will still be converted', function () {
    $reader = XmlReader::fromString('<food bestSeller="true">Berry-Berry Belgian Waffles</food>');

    expect($reader->elements())->toEqual([
        'food' => Element::make('Berry-Berry Belgian Waffles')->addAttribute('bestSeller', 'true'),
    ]);
});

test('the root element name is discarded', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    expect($reader->value('breakfast_menu.name.0')->sole())->toBe('Belgian Waffles');
});

test('can test large xml files', function () {
    $file = '/Users/samcarre/Documents/XML/psd7003.xml';

    $reader = XmlReader::fromFile($file);

    $values = $reader->value('refinfo')->lazy();

    foreach ($values as $value) {
        dd($value);
    }
})->skip();
