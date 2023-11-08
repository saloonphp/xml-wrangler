<?php

declare(strict_types=1);

test('exceptions')
    ->expect('Saloon\XmlWrangler\Exceptions')
    ->toBeClasses()
    ->toExtend(Exception::class)
    ->toUseStrictTypes()
    ->toHaveSuffix('Exception');
