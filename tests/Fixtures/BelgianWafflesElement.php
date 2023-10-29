<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Tests\Fixtures;

use Saloon\XmlWrangler\Data\Element;

class BelgianWafflesElement extends Element
{
    public function __construct(protected string $name)
    {
        parent::__construct();
    }

    /**
     * Compose your own element
     */
    protected function compose(): void
    {
        $this
             ->setAttributes([
                 'soldOut' => 'false',
                 'bestSeller' => 'true',
             ])
             ->setContent([
                 'name' => $this->name,
                 'price' => '$5.95',
                 'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
                 'calories' => '650',
             ]);
    }
}
