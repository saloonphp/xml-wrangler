<?php

declare(strict_types=1);

use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\RootElement;

test('the xml writer can write with just a root element', function () {
    $writer = new XmlWriter;

    expect($writer->write())->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root/>

XML
    );
});

test('you can set custom encoding and version on the writer', function () {
    $writer = new XmlWriter;

    $writer->setXmlEncoding('ISO-8859-1');
    $writer->setXmlVersion('2.0');

    $contents = $writer->write(['a' => 'b']);

    expect($contents)->toBe(
        <<<XML
<?xml version="2.0" encoding="ISO-8859-1"?>
<root>
  <a>b</a>
</root>

XML
    );
});

test('xml can be minified', function () {
    $writer = new XmlWriter;

    $contents = $writer->write(['a' => 'b'], true);

    expect($contents)->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root><a>b</a></root>

XML
    );
});

test('the xml writer can accept a custom root element', function () {
    $writer = new XmlWriter;

    $writer->setRootElement(new RootElement('Envelope', ['a' => 'b'], ['attribute' => 'value'], ['url' => 'https://google.com']));

    expect($writer->write())->toBe(
        <<<XML
<?xml version="1.0" encoding="utf-8"?>
<Envelope attribute="value" xmlns:url="https://google.com">
  <a>b</a>
</Envelope>

XML
    );
});
