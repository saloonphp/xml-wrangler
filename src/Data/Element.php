<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

use Saloon\XmlWrangler\XmlWriter;
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

    /**
     * Convert the element into an array
     *
     * Todo: Test this
     */
    public function toArray(): array
    {
        return XmlWriter::convertElementIntoArray($this);
    }

    // Todo: Reconsider name
    public function flatten(): string|array
    {
        $content = $this->content;

        if (! is_array($content)) {
            return $content;
        }

        dd($content);
    }
}
