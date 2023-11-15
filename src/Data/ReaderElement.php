<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

use Saloon\XmlWrangler\Contracts\Readable;
use Saloon\XmlWrangler\LazyQuery;
use Saloon\XmlWrangler\Query;
use Saloon\XmlWrangler\XmlReader;
use Saloon\XmlWrangler\XmlWriter;

class ReaderElement extends Element implements Readable
{
    /**
     * The name of the element
     */
    protected string $name;

    /**
     * The XML reader
     */
    protected XmlReader $reader;

    /**
     * XML Reader Options
     *
     * @var array<string, mixed>
     */
    protected array $readerOptions = [];

    /**
     * Create an element from a reader
     *
     * @param array<string, mixed> $options
     */
    public static function fromReader(string $name, array $options = []): static
    {
        $instance = new self;

        $instance->name = $name;
        $instance->readerOptions = $options;

        return $instance;
    }

    public function reader(): XmlReader
    {
        $xml = XmlWriter::make()->write(new RootElement($this->name, $this->getContent(), $this->getAttributes()), []);

        $reader = XmlReader::fromString($xml);

        // Todo: Set reader options

        return $reader;
    }

    /**
     * @inheritDoc
     */
    public function elements(): array
    {
        return $this->reader()->elements();
    }

    /**
     * @inheritDoc
     */
    public function element(string $name, array $withAttributes = []): LazyQuery
    {
        return $this->reader()->element($name, $withAttributes);
    }

    /**
     * @inheritDoc
     */
    public function xpathElement(string $query): Query
    {
        return $this->reader()->xpathElement($query);
    }

    /**
     * @inheritDoc
     */
    public function values(): array
    {
        return $this->reader()->values();
    }

    /**
     * @inheritDoc
     */
    public function value(string $name, array $attributes = []): LazyQuery
    {
        return $this->reader()->value($name, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function xpathValue(string $query): Query
    {
        return $this->reader()->xpathValue($query);
    }
}
