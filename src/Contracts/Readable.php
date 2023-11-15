<?php

namespace Saloon\XmlWrangler\Contracts;

use Saloon\XmlWrangler\Data\ReaderElement;
use Saloon\XmlWrangler\LazyQuery;
use Saloon\XmlWrangler\Query;

interface Readable
{
    /**
     * Get all elements
     *
     * @return array<string, ReaderElement>
     * @throws \Throwable
     */
    public function elements(): array;

    /**
     * Find an element from the XML
     *
     * @param array<string, string> $withAttributes
     * @return \Saloon\XmlWrangler\LazyQuery<ReaderElement>
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function element(string $name, array $withAttributes = []): LazyQuery;

    /**
     * Search for an element with xpath
     *
     * @return \Saloon\XmlWrangler\Query<ReaderElement>
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function xpathElement(string $query): Query;

    /**
     * Convert the XML into an array
     *
     * @return array<string, mixed>
     */
    public function values(): array;

    /**
     * Find and retrieve value of element
     *
     * @param array<string, string> $attributes
     * @return \Saloon\XmlWrangler\LazyQuery<mixed>
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public function value(string $name, array $attributes = []): LazyQuery;

    /**
     * Find and retrieve value of element
     *
     * @return \Saloon\XmlWrangler\Query<mixed>
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function xpathValue(string $query): Query;
}
