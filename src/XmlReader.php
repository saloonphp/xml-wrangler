<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use DOMXPath;
use Exception;
use DOMElement;
use DOMDocument;
use InvalidArgumentException;
use VeeWee\Xml\Reader\Reader;
use VeeWee\Xml\Reader\Matcher;
use Saloon\XmlWrangler\Data\Element;
use function VeeWee\Xml\Encoding\xml_decode;
use function VeeWee\Xml\Encoding\element_decode;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;

class XmlReader
{
    /**
     * XML Reader
     */
    protected Reader $reader;

    /**
     * Temporary File For Stream
     *
     * @var resource|null
     */
    protected mixed $streamFile = null;

    /**
     * Constructor
     *
     * @param resource $streamFile
     */
    public function __construct(Reader $reader, mixed $streamFile = null)
    {
        if (isset($streamFile) && ! is_resource($streamFile)) {
            throw new InvalidArgumentException('Parameter $streamFile provided must be a valid resource.');
        }

        $this->reader = $reader;
        $this->streamFile = $streamFile;
    }

    /**
     * Create the XML reader for a string
     */
    public static function fromString(string $xml): static
    {
        return new static(Reader::fromXmlString($xml));
    }

    /**
     * Create the XML reader for a file
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public static function fromFile(string $xml): static
    {
        if (! file_exists($xml) || ! is_readable($xml)) {
            throw new XmlReaderException(sprintf('Unable to read the [%s] file.', $xml));
        }

        return new static(Reader::fromXmlFile($xml));
    }

    /**
     * Create the reader from a stream
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public static function fromStream(mixed $resource): static
    {
        if (! is_resource($resource)) {
            throw new XmlReaderException('Resource provided must be a valid resource.');
        }

        $temporaryFile = tmpfile();

        if ($temporaryFile === false) {
            throw new XmlReaderException('Unable to create the temporary file.');
        }

        while (! feof($resource)) {
            if ($bytes = fread($resource, 1024)) {
                fwrite($temporaryFile, $bytes);
            }
        }

        rewind($temporaryFile);

        return new static(
            reader: Reader::fromXmlFile(stream_get_meta_data($temporaryFile)['uri']),
            streamFile: $temporaryFile,
        );
    }

    /**
     * Get all elements
     *
     * @return array<string, Element>
     */
    public function elements(): array
    {
        $search = $this->reader->provide(Matcher\all());

        $results = iterator_to_array($search);

        return array_map($this->parseXml(...), $results)[0];
    }

    /**
     * Recursively search through elements
     *
     * This method only keeps one element in memory at a time.
     *
     * @return array<int, string>
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    protected function searchRecursively(string $query, bool $nullable, string $buffer = null): array
    {
        $searchTerms = explode('.', $query);

        $reader = isset($buffer) ? Reader::fromXmlString($buffer) : $this->reader;

        $searchTerm = $searchTerms[0];

        $results = $reader->provide(
            Matcher\node_name($searchTerm),
        );

        array_shift($searchTerms);

        $onLastSearchTerm = empty($searchTerms);

        $elements = [];

        foreach ($results as $index => $result) {
            if ($onLastSearchTerm === true) {
                $elements[] = $result;
                continue;
            }

            $nextSearchTerm = $searchTerms[0];
            $nestedSearchTerms = $searchTerms;

            if (is_numeric($nextSearchTerm)) {
                $result = $index === (int)$nextSearchTerm ? $result : null;

                array_shift($nestedSearchTerms);
            }

            if (is_null($result)) {
                continue;
            }

            if (empty($nestedSearchTerms)) {
                $elements[] = $result;
                continue;
            }

            $elements = array_merge($elements, $this->searchRecursively(implode('.', $nestedSearchTerms), $nullable, $result));
        }

        if (empty($elements)) {
            return $nullable ? [] : throw new XmlReaderException(sprintf('Unable to find [%s] element', $searchTerm));
        }

        return $elements;
    }

    /**
     * Find an element from the XML
     *
     * @param array<string, string> $withAttributes
     * @return \Saloon\XmlWrangler\Data\Element|array<string, Element>|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public function element(string $name, array $withAttributes = [], bool $nullable = false): Element|array|null
    {
        try {
            $results = $this->searchRecursively($name, $nullable);

            // We'll parse each element in the results which will convert the XML into an
            // Element class.

            $results = array_map($this->parseXml(...), $results);

            // Flatten the array of results because the key will always be the last search term
            // that we looked for.

            $results = array_map(static function (array $element) {
                return $element[array_key_first($element)];
            }, $results);

            // Now, if there are any attributes defined we will refine our search for this.

            if (! empty($withAttributes)) {
                $results = array_filter($results, static function (Element $element) use ($withAttributes) {
                    $attributes = $element->getAttributes();

                    foreach ($withAttributes as $key => $attribute) {
                        if (($attributes[$key] ?? null) !== $attribute) {
                            return false;
                        }
                    }

                    return true;
                });

                $results = array_values($results);
            }

            if (empty($results)) {
                return $nullable ? null : throw new XmlReaderException('Unable to find element.');
            }

            // Return the results

            return count($results) === 1 ? $results[0] : $results;
        } catch (Exception $exception) {
            $this->__destruct();

            throw $exception;
        }
    }

    /**
     * Convert the XML into an array
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->convertElementArrayIntoValues($this->elements());
    }

    /**
     * Find and retrieve value of element
     *
     * @param array<string, string> $withAttributes
     * @return \Saloon\XmlWrangler\Data\Element|array<string, mixed>|string|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public function value(string $name, array $withAttributes = [], bool $nullable = false): Element|array|string|null
    {
        $value = $this->element($name, $withAttributes, $nullable);

        if ($value instanceof Element) {
            $value = $value->getContent();
        }

        if (! is_array($value)) {
            return $value;
        }

        return $this->convertElementArrayIntoValues($value);
    }

    /**
     * Search for an element with xpath
     *
     * @return \Saloon\XmlWrangler\Data\Element|array<string, Element>|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function xpathElement(string $query, bool $nullable = false): Element|array|null
    {
        $xmlString = iterator_to_array($this->reader->provide(Matcher\all()))[0];

        $dom = new DOMDocument;
        $dom->loadXML($xmlString);

        $elements = (new DOMXPath($dom))->query($query);

        if ($elements === false || $elements->count() === 0) {
            return $nullable ? null : throw new XmlReaderException(sprintf('No results found for [%s].', $query));
        }

        $results = [];

        foreach ($elements as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            $decodedElement = element_decode($element);
            $firstKey = (string)array_key_first($decodedElement);

            $results[] = $this->convertArrayIntoElements($firstKey, $decodedElement[$firstKey]);
        }

        $results = array_map(static function (array $element) {
            return $element[array_key_first($element)];
        }, $results);

        return count($results) === 1 ? $results[0] : $results;
    }

    /**
     * Find and retrieve value of element
     *
     * @return \Saloon\XmlWrangler\Data\Element|array<string, mixed>|string|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function xpathValue(string $name, bool $nullable = false): Element|array|string|null
    {
        $value = $this->xpathElement($name, $nullable);

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
     *
     * @param array<string, mixed> $elements
     * @return array<string, mixed>
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
     * @return \Saloon\XmlWrangler\Data\Element|array<string, mixed>
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
     *
     * @return array<string, mixed>|\Saloon\XmlWrangler\Data\Element
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

    /**
     * Handle destructing the reader
     *
     * Close the temporary file if it is present
     */
    public function __destruct()
    {
        if (isset($this->streamFile)) {
            fclose($this->streamFile);
            unset($this->streamFile);
        }
    }
}
