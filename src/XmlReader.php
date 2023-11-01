<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Generator;
use LogicException;
use Throwable;
use DOMElement;
use Saloon\Http\Response;
use VeeWee\Xml\Dom\Document;
use InvalidArgumentException;
use VeeWee\Xml\Reader\Node\NodeSequence;
use VeeWee\Xml\Reader\Reader;
use VeeWee\Xml\Reader\Matcher;
use Saloon\XmlWrangler\Data\Element;
use Psr\Http\Message\MessageInterface;
use function VeeWee\Xml\Encoding\xml_decode;
use function VeeWee\Xml\Encoding\element_decode;
use Saloon\XmlWrangler\Exceptions\XmlReaderException;
use function VeeWee\Xml\ErrorHandling\stop_on_first_issue;

class XmlReader
{
    /**
     * XML Reader
     */
    protected Reader $reader;

    /**
     * Root element name
     */
    protected string $rootElementName;

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

        $this->loadRootElementName();
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
     * @return array<string, Element>|Generator
     * @throws \Throwable
     */
    public function elements(bool $asGenerator = false): array|Generator
    {
        try {
            $results = $this->reader->provide(Matcher\document_element());

            $results = function () use ($results): Generator {
                foreach ($results as $result) {
                    yield from $this->parseXml($result);
                }
            };

            return $asGenerator === true ? $results() : iterator_to_array($results());
        } catch (Throwable $throwable) {
            $this->__destruct();

            throw $throwable;
        }
    }

    /**
     * Find an element from the XML
     *
     * @param array<string, string> $withAttributes
     * @return \Saloon\XmlWrangler\Data\Element|Generator|array<string, Element>|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException|\Throwable
     */
    public function element(string $name, array $withAttributes = [], bool $nullable = false, bool $asGenerator = false): Element|Generator|array|null
    {
        try {
            $searchTerms = explode('.', $name);

            // Remove the root element name because we search underneath it

            if ($searchTerms[0] === $this->rootElementName) {
                array_shift($searchTerms);
            }

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

            // If there is more than one matcher, then we should wrap the matchers in a sequence.
            // This will mean that each matcher will only use the results from the previous
            // matcher.

            if (count($matchers) > 1) {
                $matchers = [Matcher\sequence(...$matchers)];
            }

            $results = $this->reader->provide(
                Matcher\nested(
                    Matcher\document_element(),
                    ...$matchers
                ),
            );

            // Now we'll create our own generator around the generator provided by the results.
            // This allows us to map and convert each XML element into our own element keeping
            // memory usage low.

            $lastSearchTerm = $searchTerms[$lastSearchTermIndex];
            $isLastSearchTermNumeric = is_numeric($lastSearchTerm);

            $results = function () use ($results, $nullable, $name, $lastSearchTerm, $isLastSearchTermNumeric): Generator {
                $hasYieldedResult = false;

                foreach ($results as $index => $result) {
                    if ($isLastSearchTermNumeric && $index !== (int)$lastSearchTerm) {
                        continue;
                    }

                    $element = $this->parseXml($result);

                    yield $element[array_key_first($element)];

                    $hasYieldedResult = true;
                }

                if ($hasYieldedResult === false && $nullable === false) {
                    throw new XmlReaderException(sprintf('Unable to find matches for [%s]', $name));
                }
            };

            if ($asGenerator === true) {
                return $results();
            }

            $results = iterator_to_array($results());

            if (empty($results) && $nullable === true) {
                return null;
            }

            return count($results) === 1 ? $results[0] : $results;
        } catch (Throwable $throwable) {
            $this->__destruct();

            throw $throwable;
        }
    }

    /**
     * Search for an element with xpath
     *
     * @return \Saloon\XmlWrangler\Data\Element|array<string, Element>|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException|\Throwable
     */
    public function xpathElement(string $query, bool $nullable = false): Element|array|null
    {
        try {
            $xml = iterator_to_array($this->reader->provide(Matcher\document_element()))[0];

            $document = Document::fromXmlString($xml);
            $xpath = $document->xpath();

            $elements = $xpath->query($query);

            if ($elements->count() === 0) {
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
        } catch (Throwable $throwable) {
            $this->__destruct();

            throw $throwable;
        }
    }

    /**
     * Convert the XML into an array
     *
     * @return array<string, mixed>|Generator
     * @throws \Throwable
     */
    public function values(bool $asGenerator = false): array|Generator
    {
        return $this->convertElementArrayIntoValues($this->elements($asGenerator));
    }

    /**
     * Find and retrieve value of element
     *
     * @param array<string, string> $withAttributes
     * @return \Saloon\XmlWrangler\Data\Element|Generator|array<string, mixed>|string|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException|\Throwable
     */
    public function value(string $name, array $withAttributes = [], bool $nullable = false, bool $asGenerator = false): Element|Generator|array|string|null
    {
        $value = $this->element($name, $withAttributes, $nullable, $asGenerator);

        if ($value instanceof Element) {
            $value = $value->getContent();
        }

        if (! is_array($value) && ! $value instanceof Generator) {
            return $value;
        }

        return $this->convertElementArrayIntoValues($value);
    }

    /**
     * Find and retrieve value of element
     *
     * @return \Saloon\XmlWrangler\Data\Element|array<string, mixed>|string|null
     * @throws \Saloon\XmlWrangler\Exceptions\XmlReaderException
     * @throws \VeeWee\Xml\Encoding\Exception\EncodingException|\Throwable
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

    /**
     * Load the root element name of the document
     *
     * @return void
     */
    private function loadRootElementName(): void
    {
        try {
            $this->reader->provide(
                function (NodeSequence $nodeSequence) {
                    $this->rootElementName = $nodeSequence->current()->name();

                    throw new LogicException;
                }
            )->current();
        } catch (LogicException) {
            //
        }
    }
}
