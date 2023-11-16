<?php

declare(strict_types=1);

use Saloon\XmlWrangler\XmlReader;

test('the reader element can instantiate a reader instance', function () {
    $reader = XmlReader::fromFile('tests/Fixtures/breakfast-menu.xml');


    // Todo: This doens't work
    // dd($reader->element('breakfast_menu')->sole()->getContent()['food']->elements());

    $belgianWaffles = $reader->element('food.0')->sole();

    dd($belgianWaffles->elements());
//
//    dump('Is Sold Out? ' . $belgianWaffles->getAttribute('soldOut'));
//
//    dd($belgianWaffles->values());

    // Todo: Current issues:
    // - Methods on the reader have ended up in the methods of the ReaderElement
    // - values() method not longer working

    $berryBerryWaffles = $reader->element('food.2')->sole();

    dd($berryBerryWaffles->element('name')->sole());

    // Todo: Values should act like normal values and not wrap the name?

    dd($berryBerryWaffles->value('calories')->sole());
});
