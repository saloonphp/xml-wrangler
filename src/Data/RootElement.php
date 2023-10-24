<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

use Saloon\XmlWrangler\Traits\HasContent;
use Saloon\XmlWrangler\Traits\HasAttributes;

class RootElement
{
    use HasAttributes;
    use HasContent;

    /**
     * Name of the root element
     */
    protected string $name;

    /**
     * Constructor
     */
    public function __construct(string $name, mixed $content = null, array $attributes = [], array $namespaces = [])
    {
        $this->name = $name;

        $this->setContent($content)
            ->setAttributes($attributes)
            ->setNamespaces($namespaces);
    }

    /**
     * Create a root element instance
     */
    public static function make(string $name, mixed $content = null, array $attributes = [], array $namespaces = []): static
    {
        return new static($name, $content, $attributes, $namespaces);
    }

    /**
     * Get the name of the root element
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the name prefix
     */
    public function getNamePrefix(bool $includeGlue = false): ?string
    {
        if (! str_contains($this->name, ':')) {
            return null;
        }

        $glue = $includeGlue ? ':' : null;

        return strtok($this->name, ':') . $glue;
    }

    /**
     * Set the name of the root element
     *
     * @return $this
     */
    public function setName(string $name): RootElement
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Convert the root element into an element
     */
    public function toElement(): Element
    {
        return new Element($this->getContent(), $this->getAttributes());
    }
}
