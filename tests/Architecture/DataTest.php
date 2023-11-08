<?php

test('data')
    ->expect('Saloon\XmlWrangler\Data')
    ->toBeClasses()
    ->toExtendNothing()
    ->toUseStrictTypes();
