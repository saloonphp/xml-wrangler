<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Exception;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use VeeWee\Xml\Reader\Reader;
use VeeWee\Xml\Reader\Matcher;
use Saloon\XmlWrangler\Data\Element;
use function VeeWee\Xml\Encoding\xml_decode;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;

class XmlReader
{
    /**
     * XML Reader
     *
     * @var \VeeWee\Xml\Reader\Reader
     */
    protected Reader $reader;

    /**
     * Temporary File For Stream
     *
     * @var resource
     */
    protected mixed $streamFile;

    /**
     * Constructor
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
            fwrite($temporaryFile, fread($resource, 1024));
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
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function elements(): array
    {
        $search = $this->reader->provide(Matcher\all());

        $results = iterator_to_array($search);

        return array_map(fn (string $result) => $this->parseXml($result), $results)[0];
    }

    /**
     * Find an element from the XML
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function element(string $name, array $withAttributes = [], bool $nullable = false, mixed $buffer = null): Element|array|null
    {
        try {
            $names = explode('.', $name);

            // Instantiate the reader and search for our first name.

            $reader = ! empty($buffer) ? Reader::fromXmlString($buffer) : $this->reader;

            $searchTerm = $names[0];

            array_shift($names);

            ray($searchTerm, $buffer);

            $search = $reader->provide(
                Matcher\all(
                    Matcher\node_name($searchTerm),
                ),
            );

            $results = [];

            $nextSearchElement = $names[0] ?? null;

            foreach ($search as $key => $element) {
                if (is_null($nextSearchElement)) {
                    $results[] = $element;
                    continue;
                }

                // When the next search element is numeric we need to check if the key
                // of the results matches the next search element - if it does, we
                // can add it to our elements array and continue.

                if (is_numeric($nextSearchElement)) {
                    if ((int)$nextSearchElement !== $key) {
                        continue;
                    }

                    $results[] = $element;
                    array_shift($names);

                    if (empty($names)) {
                        $name = strtok($name, '.');
                    }

                    break;
                }

                $results[] = $element;
            }

            if (empty($results)) {
                return $nullable ? null : throw new XmlReaderException(sprintf('Unable to find [%s] element', $name));
            }

            if (count($names) > 0) {
                return $this->element(implode('.', $names), $withAttributes, $nullable, implode(PHP_EOL, $results));
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

        } catch (Exception $exception) {
            $this->__destruct();

            throw $exception;
        }
    }

    /**
     * Find an element from the XML
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function elementOld(string $name, array $withAttributes = [], bool $nullable = false, mixed $buffer = null): Element|array|null
    {
        try {
            $names = explode('.', $name);

            // Instantiate the reader and search for our first name.

            $reader = ! empty($buffer) ? Reader::fromXmlString($buffer) : $this->reader;

            $search = $reader->provide(
                Matcher\all(
                    Matcher\node_name($names[0])
                ),
            );

            // Convert the results into an array - this will cause us to store the full array in memory.

            $results = iterator_to_array($search);

            if (empty($results)) {
                return $nullable ? null : throw new XmlReaderException(sprintf('Unable to find [%s] element', $name));
            }

            // When there are multiple search terms we'll run the find method again on the
            // other search terms to search within an element.

            if (count($names) > 1) {
                array_shift($names);

                // When the next search term is an array we need to change our logic a little bit.
                // We need to create a new $results array with the result requested. If this
                // doesn't exist we need to throw an exception like normal.

                // If it does exist, then we need to shift the number off the array and continue
                // looking. If there are more values we'll continue looking, otherwise we'll
                // just return this value.

                if (is_numeric($names[0])) {
                    $results = [$results[$names[0]] ?? null];

                    if (is_null($results[0])) {
                        return $nullable ? null : throw new XmlReaderException(sprintf('Unable to find [%s] element', $name));
                    }

                    array_shift($names);

                    // When there are no more elements to search for we will overwrite the name of the outer
                    // array so our logic  to find the result will work.

                    if (empty($names)) {
                        $name = strtok($name, '.');
                    }
                }

                // We'll continue searching if there are additional names to look through.

                if (! empty($names)) {
                    return $this->element(implode('.', $names), $withAttributes, $nullable, implode('', $results));
                }
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
        } catch (Exception $exception) {
            $this->__destruct();

            throw $exception;
        }
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
    public function value(string $name, array $withAttributes = [], bool $nullable = false): mixed
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

    public function xpath(string $query): mixed
    {
        $xml = iterator_to_array($this->reader->provide(Matcher\all()))[0];

        $reader = new Crawler($xml);

        dd($reader);
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
