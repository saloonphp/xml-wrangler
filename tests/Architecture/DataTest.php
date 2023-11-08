<?php

declare(strict_types=1);

test('data')
    ->expect('Saloon\XmlWrangler\Data')
    ->toBeClasses()
    ->toExtendNothing()
    ->toUseStrictTypes();
