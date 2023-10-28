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
     *
     * @param array<string, string> $attributes
     * @param array<string, string> $namespaces
     */
    public function __construct(mixed $content = null, array $attributes = [], array $namespaces = [])
    {
        $this
            ->setContent($content)
            ->setAttributes($attributes)
            ->setNamespaces($namespaces);

        static::compose($this);
    }

    /**
     * Create an element instance
     *
     * @param array<string, string> $attributes
     * @param array<string, string> $namespaces
     */
    public static function make(mixed $content = null, array $attributes = [], array $namespaces = []): static
    {
        return new static($content, $attributes, $namespaces);
    }

    /**
     * Define your own XML element
     */
    protected static function compose(Element $element): void
    {
        //
    }
}
