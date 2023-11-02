<?php

test('exceptions')
    ->expect('Saloon\XmlWrangler\Exceptions')
    ->toBeClasses()
    ->toExtend(Exception::class)
    ->toUseStrictTypes()
    ->toHaveSuffix('Exception');
