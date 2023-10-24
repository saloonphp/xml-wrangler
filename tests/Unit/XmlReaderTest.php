<?php

declare(strict_types=1);

use Saloon\XmlWrangler\XmlReader;

test('you can parse xml', function () {
    $file = file_get_contents('tests/Fixtures/ExampleFiles/origo.xml');

    // Todo: What could bring this back is a ->search() method so I could
    // Todo: just find something like $parser->search('ds:X509IssuerName') and it'll return the element
    // Todo: Or we could do ->search('mtg:KeyInfo.ds:X509SubjectName') if there is ambiguity

    //    $file = (new \Saloon\SoapPlugin\XmlWriter())->write([
    //        'nested' => [
    //            'value' => [1, 2, 3],
    //        ],
    //    ]);

    $reader = XmlReader::make($file);

    // Todo: Come up with better method names

    // $reader->element('value', nullable: true)
    // $reader->value('value', nullable: true)
    // $reader->elements([], nullable: true): array
    // $reader->values([], nullable: true): array

    // $reader->elements(); Just elements (no searching)
    // $reader->values(); Just values (no searching)

    $valuations = $reader->value('ce:valuation');

    dd($valuations);

    $values = $reader->value('ce:m_content.ce:contract');

    $fundName = $reader->value('ce:fund_name');
    $numberOfUnits = $reader->value('ce:number_of_units');

    dd($values, $fundName, $numberOfUnits);
});
