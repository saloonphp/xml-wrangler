<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Tests\Fixtures;

use Saloon\XmlWrangler\Data\Element;

class BelgianWafflesElement extends Element
{
    /**
     * Compose your own element
     */
    protected static function compose(Element $element): void
    {
        $element
            ->setAttributes([
                'soldOut' => 'false',
                'bestSeller' => 'true',
            ])
            ->setContent([
                'name' => 'Belgian Waffles',
                'price' => '$5.95',
                'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
                'calories' => '650',
            ]);
    }
}
