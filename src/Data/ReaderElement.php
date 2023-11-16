<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

use Saloon\XmlWrangler\Contracts\Readable;
use Saloon\XmlWrangler\LazyQuery;
use Saloon\XmlWrangler\Query;
use Saloon\XmlWrangler\XmlReader;
use Saloon\XmlWrangler\XmlWriter;
use function Psl\Type\instance_of;

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
        $content = $this->content;

        // Occasionally, the content of the element is an array of other elements. When this
        // happens we need to wrap the content in another element with the same name.

        if (is_int(array_key_first($content))) {
            $content = [$this->name => $content];
        }

        $xml = XmlWriter::make()->write(new RootElement($this->name, $content, $this->attributes), []);

        // Todo: Set reader options

        return XmlReader::fromString($xml);
    }

    /**
     * @inheritDoc
     */
    public function elements(): array
    {
        return $this->reader()->elements()[$this->name]->getContent();
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
        return $this->reader()->values()[$this->name];
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
