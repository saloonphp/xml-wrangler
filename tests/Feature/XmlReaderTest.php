<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Saloon\XmlWrangler\Query;
use Saloon\XmlWrangler\LazyQuery;
use Saloon\XmlWrangler\XmlReader;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\XmlWrangler\Data\ReaderElement;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;
use Saloon\XmlWrangler\Tests\Fixtures\Saloon\BreakfastMenuRequest;

test('can parse xml and convert it into an array of elements', function () {
    $file = file_get_contents('tests/Fixtures/breakfast-menu.xml');

    $reader = XmlReader::fromString($file);

    $belgianWaffles = ReaderElement::make([
        'name' => ReaderElement::make('Belgian Waffles'),
        'price' => ReaderElement::make('$5.95'),
        'description' => ReaderElement::make('Two of our famous Belgian Waffles with plenty of real maple syrup'),
        'calories' => ReaderElement::make('650'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'true',
    ]);

    $strawberryBelgianWaffles = ReaderElement::make([
        'name' => ReaderElement::make('Strawberry Belgian Waffles'),
        'price' => ReaderElement::make('$7.95'),
        'description' => ReaderElement::make('Light Belgian waffles covered with strawberries and whipped cream'),
        'calories' => ReaderElement::make('900'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'false',
    ]);

    $berryberryBelgianWaffles = ReaderElement::make([
        'name' => ReaderElement::make('Berry-Berry Belgian Waffles'),
        'price' => ReaderElement::make('$8.95'),
        'description' => ReaderElement::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
        'calories' => ReaderElement::make('900'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'true',
    ]);

    $frenchToast = ReaderElement::make([
        'name' => ReaderElement::make('French Toast'),
        'price' => ReaderElement::make('$4.50'),
        'description' => ReaderElement::make('Thick slices made from our homemade sourdough bread'),
        'calories' => ReaderElement::make('600'),
    ])->setAttributes([
        'soldOut' => 'true', 'bestSeller' => 'false',
    ]);

    $homestyleBreakfast = ReaderElement::make([
        'name' => ReaderElement::make('Homestyle Breakfast'),
        'price' => ReaderElement::make('$6.95'),
        'description' => ReaderElement::make('Two eggs, bacon or sausage, toast, and our ever-popular hash browns'),
        'calories' => ReaderElement::make('950'),
    ])->setAttributes([
        'soldOut' => 'false', 'bestSeller' => 'false',
    ]);

    $result = [
        'breakfast_menu' => ReaderElement::make([
            'food' => new ReaderElement([
                $belgianWaffles,
                $strawberryBelgianWaffles,
                $berryberryBelgianWaffles,
                $frenchToast,
                $homestyleBreakfast,
            ]),
        ])->addAttribute('name', 'Big G\'s Breakfasts'),
    ];

    dd($reader->elements()['breakfast_menu']->getContent()['food']->values());

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

    expect($element)->toBeInstanceOf(ReaderElement::class);
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

    expect($soldOut)->toBeInstanceOf(ReaderElement::class);

    expect($soldOut)->toEqual(
        ReaderElement::make()->setAttributes(['soldOut' => 'true', 'bestSeller' => 'false'])->setContent([
            'name' => ReaderElement::make('French Toast'),
            'price' => ReaderElement::make('$4.50'),
            'description' => ReaderElement::make('Thick slices made from our homemade sourdough bread'),
            'calories' => ReaderElement::make('600'),
        ]),
    );

    $bestSellers = $reader->element('food', ['bestSeller' => 'true'])->get();

    expect($bestSellers)->toBeArray();

    expect($bestSellers)->toEqual([
        ReaderElement::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => ReaderElement::make('Belgian Waffles'),
            'price' => ReaderElement::make('$5.95'),
            'description' => ReaderElement::make('Two of our famous Belgian Waffles with plenty of real maple syrup'),
            'calories' => ReaderElement::make('650'),
        ]),
        ReaderElement::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => ReaderElement::make('Berry-Berry Belgian Waffles'),
            'price' => ReaderElement::make('$8.95'),
            'description' => ReaderElement::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
            'calories' => ReaderElement::make('900'),
        ]),
    ]);

    $notSoldOutNotBestSeller = $reader->element('food', ['soldOut' => 'false', 'bestSeller' => 'false'])->get();

    expect($notSoldOutNotBestSeller)->toEqual([
        ReaderElement::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'false'])->setContent([
            'name' => ReaderElement::make('Strawberry Belgian Waffles'),
            'price' => ReaderElement::make('$7.95'),
            'description' => ReaderElement::make('Light Belgian waffles covered with strawberries and whipped cream'),
            'calories' => ReaderElement::make('900'),
        ]),
        ReaderElement::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'false'])->setContent([
            'name' => ReaderElement::make('Homestyle Breakfast'),
            'price' => ReaderElement::make('$6.95'),
            'description' => ReaderElement::make('Two eggs, bacon or sausage, toast, and our ever-popular hash browns'),
            'calories' => ReaderElement::make('950'),
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
        ReaderElement::make()->setAttributes(['soldOut' => 'false', 'bestSeller' => 'true'])->setContent([
            'name' => ReaderElement::make('Berry-Berry Belgian Waffles'),
            'price' => ReaderElement::make('$8.95'),
            'description' => ReaderElement::make('Light Belgian waffles covered with an assortment of fresh berries and whipped cream'),
            'calories' => ReaderElement::make('900'),
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
        'food' => ReaderElement::make('Berry-Berry Belgian Waffles')->addAttribute('bestSeller', 'true'),
    ]);
});

test('the root element name is discarded', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');

    expect($reader->value('breakfast_menu.name.0')->sole())->toBe('Belgian Waffles');
});

test('can read deeply nested items', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/nested-breakfast-menu.xml');

    expect($reader->value('food.ingredient')->get())->toEqual([
        'Sugar', 'Berries', 'Bread', 'Egg',
    ]);
});

test('root namespaces are removed from xpath queries', function () {
    $reader = XmlReader::fromString(
        <<<XML
<container xmlns="http://symfony.com/schema/dic/services" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd">
  <services>
    <service id="service_container" class="Symfony\Component\DependencyInjection\ContainerInterface" public="true" synthetic="true"/>
    <service id="kernel" class="TicketSwap\Kernel" public="true" synthetic="true" autoconfigure="true">
      <tag xmlns="http://symfony.com/schema/dic/tag-1" name="controller.service_arguments">1</tag>
      <tag xmlns="http://symfony.com/schema/dic/tag-2"  name="routing.route_loader">2</tag>
    </service>
  </services>
</container>
XML
    );

    // Element should keep the xmlns

    $element = $reader->element('container.services.service', ['id' => 'service_container'])->sole();

    expect($element)->toEqual(
        ReaderElement::make('')->setAttributes([
            'id' => 'service_container',
            'class' => 'Symfony\Component\DependencyInjection\ContainerInterface',
            'public' => 'true',
            'synthetic' => 'true',
            'xmlns' => 'http://symfony.com/schema/dic/services',
        ])
    );

    // We should be able to search and the root namespaces should be missing.

    $xpathElement = $reader->xpathElement('/container/services/service[@id="service_container"]')->sole();

    expect($xpathElement)->toEqual(
        ReaderElement::make('')->setAttributes([
            'id' => 'service_container',
            'class' => 'Symfony\Component\DependencyInjection\ContainerInterface',
            'public' => 'true',
            'synthetic' => 'true',
        ])
    );

    // Or we can map them and they will be searchable

    $reader->setXpathNamespaceMap(['root' => 'http://symfony.com/schema/dic/services']);

    $mappedXpathElement = $reader->xpathElement(
        '/root:container/root:services/root:service[@id="service_container"]',
    )->sole();

    expect($mappedXpathElement)->toEqual(
        ReaderElement::make('')->setAttributes([
            'id' => 'service_container',
            'class' => 'Symfony\Component\DependencyInjection\ContainerInterface',
            'public' => 'true',
            'synthetic' => 'true',
            'xmlns' => 'http://symfony.com/schema/dic/services',
        ])
    );

    // Test that nested namespaces work too

    $tags = $reader->element('tag')->get();

    expect($tags)->toEqual([
        ReaderElement::make('1')->setAttributes(['name' => 'controller.service_arguments', 'xmlns' => 'http://symfony.com/schema/dic/tag-1']),
        ReaderElement::make('2')->setAttributes(['name' => 'routing.route_loader', 'xmlns' => 'http://symfony.com/schema/dic/tag-2']),
    ]);

    // Test we can query xpath element

    $reader->setXpathNamespaceMap([]);

    $xpathTags = $reader->xpathElement('/container/services/service/tag')->get();

    expect($xpathTags)->toEqual([
        ReaderElement::make('1')->setAttributes(['name' => 'controller.service_arguments']),
        ReaderElement::make('2')->setAttributes(['name' => 'routing.route_loader']),
    ]);

    // Test we can query xpath elements with mapping

    $reader->setXpathNamespaceMap([
        'root' => 'http://symfony.com/schema/dic/services',
        'tag-1' => 'http://symfony.com/schema/dic/tag-1',
        'tag-2' => 'http://symfony.com/schema/dic/tag-2',
    ]);

    $mappedXpathTag = $reader->xpathElement(
        '/root:container/root:services/root:service/tag-1:tag',
    )->sole();

    expect($mappedXpathTag)->toEqual(ReaderElement::make('1')
        ->setAttributes(['name' => 'controller.service_arguments', 'xmlns' => 'http://symfony.com/schema/dic/tag-1']));

    // Works with XPath Element

    $reader->setXpathNamespaceMap([
        'root' => 'http://symfony.com/schema/dic/services',
        'tag-1' => 'http://symfony.com/schema/dic/tag-1',
    ]);

    $mappedXpathTag = $reader->xpathValue(
        query: '/root:container/root:services/root:service/tag-1:tag',
    )->sole();

    expect($mappedXpathTag)->toBe('1');
});
