<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Generator;
use Throwable;
use DOMElement;
use Saloon\Http\Response;
use VeeWee\Xml\Dom\Document;
use InvalidArgumentException;
use VeeWee\Xml\Reader\Reader;
use VeeWee\Xml\Reader\Matcher;
use Saloon\XmlWrangler\Data\Element;
use Psr\Http\Message\MessageInterface;
use function VeeWee\Xml\Encoding\xml_decode;
use function VeeWee\Xml\Encoding\element_decode;
use function VeeWee\Xml\Dom\Configurator\traverse;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;
use VeeWee\Xml\Dom\Traverser\Visitor\RemoveNamespaces;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;

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
     * XPath namespace map
     *
     * Used to map un-prefixed namespaces
     *
     * @var array<string, string>
     */
    protected array $xpathNamespaceMap = [];

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
     * Create a reader from a PSR response
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public static function fromPsrResponse(MessageInterface $response): static
    {
        $stream = $response->getBody();

        if (! $stream->isReadable()) {
            throw new XmlReaderException('Unable to read from the stream.');
        }

        $temporaryFile = tmpfile();

        if ($temporaryFile === false) {
            throw new XmlReaderException('Unable to create the temporary file.');
        }

        while (! $stream->eof()) {
            if ($bytes = $stream->read(1024)) {
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
     * Create a reader from a Saloon instance
     *
     * @see https://github.com/saloonphp/saloon
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     */
    public static function fromSaloonResponse(Response $response): static
    {
        return static::fromPsrResponse($response->getPsrResponse());
    }

    /**
     * Get all elements
     *
     * @return array<string, Element>
     * @throws \Throwable
     */
    public function elements(): array
    {
        try {
            $results = $this->reader->provide(Matcher\document_element());

            $results = function () use ($results): Generator {
                foreach ($results as $result) {
                    yield from $this->parseXml($result);
                }
            };

            return iterator_to_array($results());
        } catch (Throwable $throwable) {
            $this->__destruct();

            throw $throwable;
        }
    }

    /**
     * Find an element from the XML
     *
     * @param array<string, string> $withAttributes
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \Throwable
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function element(string $name, array $withAttributes = []): LazyQuery
    {
        try {
            $searchTerms = explode('.', $name);

            $lastSearchTermIndex = array_key_last($searchTerms);

            // We'll start by creating a matcher for each search term that has been provided.

            $matchers = [];

            foreach ($searchTerms as $index => $searchTerm) {
                if (! is_numeric($searchTerm)) {
                    $matchers[$index] = Matcher\element_name($searchTerm);
                    continue;
                }

                // We won't continue if the last search term is numeric because
                // we will apply this logic later in our code.

                if ($index === $lastSearchTermIndex) {
                    continue;
                }

                // When the current search term is numeric, we need to look back at the previous
                // matcher and join the position to the last matcher.

                $previousMatcher = $matchers[$index - 1] ?? null;
                $elementPositionMatcher = Matcher\element_position((int)$searchTerm + 1);

                if (isset($previousMatcher)) {
                    $matchers[$index - 1] = Matcher\all($previousMatcher, $elementPositionMatcher);
                    continue;
                }

                $matchers[] = $elementPositionMatcher;
            }

            // When we have attributes, we have to search for the attributes on the very last element
            // so we should find the last matcher and append the attribute search onto it.

            if (! empty($withAttributes)) {
                $attributeMatchers = [];

                foreach ($withAttributes as $attributeName => $attributeValue) {
                    $attributeMatchers[] = Matcher\attribute_value($attributeName, $attributeValue);
                }

                $lastMatcherIndex = array_key_last($matchers);

                $matchers[$lastMatcherIndex] = Matcher\all($matchers[$lastMatcherIndex], ...$attributeMatchers);
            }

            $results = $this->reader->provide(
                Matcher\nested(...$matchers),
            );

            // Now we'll create our own generator around the generator provided by the results.
            // This allows us to map and convert each XML element into our own element keeping
            // memory usage low.

            $lastSearchTerm = $searchTerms[$lastSearchTermIndex];
            $isLastSearchTermNumeric = is_numeric($lastSearchTerm);

            $results = function () use ($results, $lastSearchTerm, $isLastSearchTermNumeric): Generator {
                foreach ($results as $index => $result) {
                    if ($isLastSearchTermNumeric && $index !== (int)$lastSearchTerm) {
                        continue;
                    }

                    $element = $this->parseXml($result);

                    yield $element[array_key_first($element)];
                }
            };

            return new LazyQuery($name, $results());
        } catch (Throwable $throwable) {
            $this->__destruct();

            throw $throwable;
        }
    }

    /**
     * Search for an element with xpath
     *
     * @throws \Throwable
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function xpathElement(string $query): Query
    {
        try {
            $xml = $this->reader->provide(Matcher\document_element())->current();

            $xpathConfigurators = [];
            $namespaceMap = $this->xpathNamespaceMap;

            // When the namespace map is empty we will remove the root namespaces
            // because if they are not mapped then you cannot search on them.

            if (empty($namespaceMap)) {
                $xml = Document::fromXmlString($xml, traverse(RemoveNamespaces::unprefixed()))->toXmlString();
            } else {
                $xpathConfigurators[] = namespaces($namespaceMap);
            }

            $xpath = Document::fromXmlString($xml)->xpath(...$xpathConfigurators);

            $elements = $xpath->query($query);

            $generator = function () use ($elements) {
                foreach ($elements as $element) {
                    if (! $element instanceof DOMElement) {
                        continue;
                    }

                    $decodedElement = element_decode($element);

                    $firstKey = (string)array_key_first($decodedElement);

                    $result = $this->convertArrayIntoElements($firstKey, $decodedElement[$firstKey]);

                    yield $result[array_key_first($result)];
                }
            };

            return new Query($query, $generator());
        } catch (Throwable $throwable) {
            $this->__destruct();

            throw $throwable;
        }
    }

    /**
     * Convert the XML into an array
     *
     * @throws \Throwable
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->convertElementArrayIntoValues($this->elements());
    }

    /**
     * Find and retrieve value of element
     *
     * @param array<string, string> $attributes
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \Throwable
     */
    public function value(string $name, array $attributes = []): LazyQuery
    {
        $node = $this->element($name, $attributes)->lazy();

        return new LazyQuery($name, $this->convertElementArrayIntoValues($node));
    }

    /**
     * Find and retrieve value of element
     *
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \Throwable
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException
     */
    public function xpathValue(string $query): Query
    {
        $generator = function () use ($query) {
            yield from $this->xpathElement($query)->get();
        };

        return new Query($query, $this->convertElementArrayIntoValues($generator()));
    }

    /**
     * Recursively convert element array into values
     *
     * @param array<string, mixed> $elements
     * @return array<string, mixed>|Generator
     */
    protected function convertElementArrayIntoValues(array|Generator $elements): array|Generator
    {
        $fromArray = is_array($elements);

        $generator = function () use ($elements): Generator {
            foreach ($elements as $key => $element) {
                $value = $element->getContent();

                yield $key => is_array($value) ? $this->convertElementArrayIntoValues($value) : $value;
            }
        };

        return $fromArray === true ? iterator_to_array($generator()) : $generator();
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

            // When there is just one value left and that value is "@value" we will
            // set the content of the element to be this value.

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
     * Set the XPath namespace map
     *
     * Used to map un-prefixed namespaces
     *
     * @param array<string, string> $xpathNamespaceMap
     */
    public function setXpathNamespaceMap(array $xpathNamespaceMap): XmlReader
    {
        $this->xpathNamespaceMap = $xpathNamespaceMap;

        return $this;
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
