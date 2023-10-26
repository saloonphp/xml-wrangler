<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use VeeWee\Xml\Reader\Reader;
use VeeWee\Xml\Reader\Matcher;
use Saloon\XmlWrangler\Data\Element;
use function VeeWee\Xml\Encoding\xml_decode;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;

class XmlReader
{
    /**
     * XML
     */
    protected string $xml;

    /**
     * Constructor
     */
    public function __construct(string $xml)
    {
        $this->xml = $xml;
    }

    /**
     * Create the XML reader
     */
    public static function fromString(string $xml): static
    {
        return new static($xml);
    }

    /**
     * Get all elements
     *
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function elements(): array
    {
        $reader = Reader::fromXmlString($this->xml);

        $search = $reader->provide(Matcher\all());

        $results = iterator_to_array($search);

        return array_map(fn (string $result) => $this->parseXml($result), $results)[0];
    }

    /**
     * Find an element from the XML
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function element(string $name, bool $nullable = false, string $buffer = null): Element|array|null
    {
        $names = explode('.', $name);

        $reader = Reader::fromXmlString($buffer ?? $this->xml);

        $search = $reader->provide(
            Matcher\all(
                Matcher\node_name($names[0])
            ),
        );

        $results = iterator_to_array($search);

        if (empty($results)) {
            return $nullable ? null : throw new XmlReaderException(sprintf('Unable to find [%s] element', $name));
        }

        // When there are multiple search terms we'll run the find method
        // again on the other search terms to search within an element

        if (count($names) > 1) {
            array_shift($names);

            return $this->element(implode('.', $names), $nullable, implode('', $results));
        }

        // Now we'll want to loop over each element in the results array
        // and convert the string XML into elements.

        $results = array_map(fn (string $result) => $this->parseXml($result), $results);

        if (count($results) === 1) {
            return $results[0][$name];
        }

        return array_map(static function (array $result) use ($name) {
            return $result[$name];
        }, $results);
    }

    /**
     * Convert the XML into an array
     *
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function values(): array
    {
        return $this->convertElementArrayIntoValues($this->elements());
    }

    /**
     * Find and retrieve value of element
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function value(string $name, bool $nullable = false): mixed
    {
        $value = $this->element($name, $nullable);

        if ($value instanceof Element) {
            $value = $value->getContent();
        }

        if (! is_array($value)) {
            return $value;
        }

        return $this->convertElementArrayIntoValues($value);
    }

    /**
     * Recursively convert element array into values
     */
    protected function convertElementArrayIntoValues(array $elements): array
    {
        $values = [];

        foreach ($elements as $key => $element) {
            $value = $element->getContent();

            $values[$key] = is_array($value) ? $this->convertElementArrayIntoValues($value) : $value;
        }

        return $values;
    }

    /**
     * Parse the raw XML string into elements
     *
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    protected function parseXml(string $xml): Element|array
    {
        $decoded = xml_decode($xml);

        $firstKey = array_key_first($decoded);

        return $this->convertArrayIntoElements($firstKey, $decoded[$firstKey]);
    }

    /**
     * Convert the array into elements
     */
    protected function convertArrayIntoElements(?string $key, mixed $value): array|Element
    {
        $element = new Element;

        if (is_array($value)) {
            $element->setAttributes($value['@attributes'] ?? []);
            $element->setNamespaces($value['@namespaces'] ?? []);

            unset($value['@namespaces'], $value['@attributes']);

            // Todo: Clean up nested if statements

            if (count($value) === 1 && isset($value['@value'])) {
                $element->setContent($value['@value']);
            } else {
                $nestedValues = [];

                foreach ($value as $nestedKey => $nestedValue) {
                    $nestedValues[$nestedKey] = is_array($nestedValue) ? $this->convertArrayIntoElements(null, $nestedValue) : new Element($nestedValue);
                }

                $element->setContent($nestedValues);
            }
        } else {
            $element->setContent($value);
        }

        if (is_null($key)) {
            return $element;
        }

        return [$key => $element];
    }
}
