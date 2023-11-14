<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

use Saloon\XmlWrangler\XmlReader;
use Saloon\XmlWrangler\XmlWriter;

/**
 * @mixin XmlReader
 */
class ReaderElement extends Element
{
    /**
     * The name of the element
     */
    protected string $name;

    /**
     * Set the name of the element
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name of the element
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Create a reader instance from the element
     *
     * @throws \DOMException
     * @throws \Saloon\XmlWrangler\Exceptions\XmlWriterException
     */
    public function reader(): XmlReader
    {
        $xml = XmlWriter::make()->write(new RootElement($this->getName(), $this->getContent(), $this->getAttributes()), []);

        return XmlReader::fromString($xml);
    }

    /**
     * Proxy a method call to the reader
     *
     * @param array<int, mixed> $arguments
     * @throws \DOMException
     * @throws \Saloon\XmlWrangler\Exceptions\XmlWriterException
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->reader()->$name(...$arguments);
    }
}
