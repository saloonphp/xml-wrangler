<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

use Saloon\XmlWrangler\Traits\HasContent;
use Saloon\XmlWrangler\Traits\HasAttributes;

class Element
{
    use HasAttributes;
    use HasContent;

    /**
     * Constructor
     *
     * Base XML Element DTO
     */
    public function __construct(mixed $content = null, array $attributes = [], array $namespaces = [])
    {
        $this
            ->setContent($content)
            ->setAttributes($attributes)
            ->setNamespaces($namespaces);
    }

    /**
     * Create an element instance
     */
    public static function make(mixed $content = null, array $attributes = [], array $namespaces = []): static
    {
        return new static($content, $attributes, $namespaces);
    }
}
