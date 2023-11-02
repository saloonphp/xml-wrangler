<?php

test('globals are not used')
    ->expect(['dd', 'dump', 'ray', 'sleep', 'ddd', 'die', 'exit'])
    ->not->toBeUsed();

test('strict types')
    ->expect('Saloon\XmlWrangler')
    ->toUseStrictTypes();
