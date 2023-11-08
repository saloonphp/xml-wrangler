<?php

test('traits')
    ->expect('Saloon\XmlWrangler\Traits')
    ->toBeTraits()
    ->toUseStrictTypes();
