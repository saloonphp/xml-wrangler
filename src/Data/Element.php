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

        $this->compose();
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
     * Compose the Element.
     *
     * You can use $this to add content, attributes and namespaces.
     */
    protected function compose(): void
    {
        //
    }
}
