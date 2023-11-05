<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Traits;

trait HasAttributes
{
    /**
     * Attributes
     *
     * @var array<string, string>
     */
    protected array $attributes = [];

    /**
     * Set the root namespace of the tag e.g. xmlns="url"
     *
     * @return $this
     */
    public function setRootNamespace(string $url): static
    {
        $this->attributes['xmlns'] = $url;

        return $this;
    }

    /**
     * Add a namespace to the element
     *
     * @return $this
     */
    public function addNamespace(string $name, string $url): static
    {
        if (! empty($name)) {
            $name = ':' . $name;
        }

        $this->attributes['xmlns' . $name] = $url;

        return $this;
    }

    /**
     * Add an attribute to the element
     *
     * @return $this
     */
    public function addAttribute(string $name, string|int|float|bool $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Set the attributes on the element
     *
     * @param array<string, string> $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Set the namespaces on the element
     *
     * @param array<string, string> $namespaces
     * @return $this
     */
    public function setNamespaces(array $namespaces): static
    {
        foreach ($namespaces as $name => $url) {
            $this->addNamespace($name, $url);
        }

        return $this;
    }

    /**
     * Get all attributes
     *
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get an individual attribute
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }
}
