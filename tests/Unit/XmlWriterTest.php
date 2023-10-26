<?php

declare(strict_types=1);

use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\Element;

test('a minimal element can be converted into a XML array', function () {
    $writer = new XmlWriter;

    $data = ['Person' => new Element];

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Person/>
</root>

XML
    );
});

test('you can provide scalar content', function (mixed $value, string $expected) {
    $data = ['Element' => new Element($value)];

    $writer = new XmlWriter;

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Element>{$expected}</Element>
</root>

XML
    );
})->with([
    ['howdy', 'howdy'],
    [1, '1'],
    [1.5, '1.5'],
]);

test('you can provide array content with mixed values', function () {
    $data = [
        'Saloon' => [
            'yee' => 'haw',
            'nested' => [
                'foo' => 'bar',
            ],
            'another' => [
                'abc' => new Element([
                    'def' => 'xyz',
                    'super' => new Element('nested'),
                ]),
            ],
        ],
    ];

    $writer = new XmlWriter;

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Saloon>
    <yee>haw</yee>
    <nested>
      <foo>bar</foo>
    </nested>
    <another>
      <abc>
        <def>xyz</def>
        <super>nested</super>
      </abc>
    </another>
  </Saloon>
</root>

XML
    );
});

test('you can provide just namespaces', function () {
    $element = new Element(
        namespaces: [
            null => 'https://docs.saloon.dev',
            'ns1' => 'https://google.com',
            'ns2' => 'https://github.com',
        ]
    );

    $writer = new XmlWriter;
    $data = ['Saloon' => $element];

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Saloon xmlns="https://docs.saloon.dev" xmlns:ns1="https://google.com" xmlns:ns2="https://github.com"/>
</root>

XML
    );
});

test('you can provide attributes', function () {
    $element = new Element(
        attributes: [
            'background-color' => '#09f',
        ],
    );

    $writer = new XmlWriter;
    $data = ['Saloon' => $element];

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Saloon background-color="#09f"/>
</root>

XML
    );
});

test('you can provide namespaces and attributes', function () {
    $element = new Element(
        attributes: [
            'background-color' => '#09f',
        ],
        namespaces: [
            null => 'https://docs.saloon.dev',
            'ns1' => 'https://google.com',
            'ns2' => 'https://github.com',
        ],
    );

    $writer = new XmlWriter;
    $data = ['Saloon' => $element];

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Saloon xmlns="https://docs.saloon.dev" background-color="#09f" xmlns:ns1="https://google.com" xmlns:ns2="https://github.com"/>
</root>

XML
    );
});

test('attributes with a scalar value', function () {
    $element = new Element(
        content: 'Howdy',
        attributes: [
            'background-color' => '#09f',
        ],
        namespaces: [
            null => 'https://docs.saloon.dev',
            'ns1' => 'https://google.com',
            'ns2' => 'https://github.com',
        ],
    );

    $writer = new XmlWriter;
    $data = ['Saloon' => $element];

    expect($writer->write('root', $data))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
  <Saloon xmlns="https://docs.saloon.dev" background-color="#09f" xmlns:ns1="https://google.com" xmlns:ns2="https://github.com">Howdy</Saloon>
</root>

XML
    );
});

test('maximal element test', function () {
    $element = new Element(
        content: [
            'yee' => 'haw',
            'nested' => [
                'foo' => 'bar',
            ],
            'another' => [
                'abc' => new Element([
                    'def' => 'xyz',
                    'super' => new Element('nested'),
                ]),
            ],
        ],
        attributes: [
            'background-color' => '#09f',
        ],
        namespaces: [
            null => 'https://docs.saloon.dev',
            'ns1' => 'https://google.com',
            'ns2' => 'https://github.com',
        ],
    );

    $writer = new XmlWriter;
    $data = ['Saloon' => $element];

    expect($writer->write('root', $data, true))->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root><Saloon xmlns="https://docs.saloon.dev" background-color="#09f" xmlns:ns1="https://google.com" xmlns:ns2="https://github.com"><yee>haw</yee><nested><foo>bar</foo></nested><another><abc><def>xyz</def><super>nested</super></abc></another></Saloon></root>

XML
    );
});

test('when an element has an array of items they can be merged together', function () {
    $element = new Element([
        'Header' => [
            'a' => new Element(),
            'b' => new Element([
                'Again' => [
                    'c' => new Element('howdy'),
                    'd' => new Element(),
                ],
            ]),
            'many-values' => [1, 2, 3, 4, 5],
        ],
    ]);

    $writer = new XmlWriter;
    $data = ['Saloon' => $element];

    expect($writer->write('root', $data, true))->toBe(
<<<XML
<?xml version="1.0" encoding="utf-8"?>
<root><Saloon><Header><a/><b><Again><c>howdy</c><d/></Again></b><many-values>1</many-values><many-values>2</many-values><many-values>3</many-values><many-values>4</many-values><many-values>5</many-values></Header></Saloon></root>

XML
    );
});
