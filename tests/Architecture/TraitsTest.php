<?php

declare(strict_types=1);

test('traits')
    ->expect('Saloon\XmlWrangler\Traits')
    ->toBeTraits()
    ->toUseStrictTypes();
