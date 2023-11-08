<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Closure;
use Spatie\ArrayToXml\ArrayToXml;
use Saloon\XmlWrangler\Data\CDATA;
use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\Data\RootElement;
use Saloon\XmlWrangler\Exceptions\XmlWriterException;

class XmlWriter
{
    /**
     * XML Encoding
     */
    protected string $xmlEncoding;

    /**
     * XML version
     */
    protected string $xmlVersion;

    /**
     * XML standalone
     */
    protected bool $xmlStandalone;

    /**
     * Additional processing instructions
     *
     * @var array<string, string>
     */
    protected array $processingInstructions = [];

    /**
     * Constructor
     */
    public function __construct(string $xmlEncoding = 'utf-8', string $xmlVersion = '1.0', bool $xmlStandalone = null)
    {
        $this->xmlEncoding = $xmlEncoding;
        $this->xmlVersion = $xmlVersion;

        if (! is_null($xmlStandalone)) {
            $this->xmlStandalone = $xmlStandalone;
        }
    }

    /**
     * Create an XML writer instance
     */
    public static function make(string $xmlEncoding = 'utf-8', string $xmlVersion = '1.0', bool $xmlStandalone = null): static
    {
        return new static($xmlEncoding, $xmlVersion, $xmlStandalone);
    }

    /**
     * Build the XML body
     *
     * @param array<string, mixed> $content
     * @throws \DOMException
     * @throws \Saloon\XmlWrangler\Exceptions\XmlWriterException
     */
    public function write(string|RootElement $rootElement, array $content, bool $minified = false): string
    {
        if (is_string($rootElement)) {
            $rootElement = new RootElement($rootElement);
        }

        if (! $this->isTopLevelContentValid($content)) {
            throw new XmlWriterException('The top-most level of content must not have numeric keys.');
        }

        $rootElementBuilder = [
            'rootElementName' => $rootElement->getName(),
        ];

        // We should check for any attributes that might be on the element.

        $rootElementBuilder = array_merge($rootElementBuilder, $this->buildElementAttributes($rootElement));

        $rootElementContent = $rootElement->getContent() ?? [];

        if (is_scalar($rootElementContent)) {
            $rootElementContent = ['_value' => $rootElementContent];
        }

        // Now we will convert the XML content into an array which will recursively
        // convert all the elements into their correct format.

        $content = $this->convertXmlContentIntoArray(
            array_merge($rootElementContent, $content)
        );

        $parameters = [
            $content,
            $rootElementBuilder,
            'xmlEncoding' => $this->xmlEncoding,
            'xmlVersion' => $this->xmlVersion,
        ];

        if (isset($this->xmlStandalone)) {
            $parameters['xmlStandalone'] = $this->xmlStandalone;
        }

        $engine = new ArrayToXml(...$parameters);

        // Processing instructions

        foreach ($this->processingInstructions as $target => $instruction) {
            $engine->addProcessingInstruction($target, $instruction);
        }

        // Minification

        if ($minified === false) {
            $engine->prettify();
        }

        return $engine->toXml();
    }

    /**
     * Validate top level content
     *
     * @param array<string, mixed> $content
     */
    protected function isTopLevelContentValid(array $content): bool
    {
        foreach ($content as $key => $unused) {
            if (is_numeric($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert an element into an array
     *
     * @return array<string, mixed>
     */
    protected function convertElementIntoArray(Element $element): array
    {
        return array_merge($this->buildElementAttributes($element), $this->buildElementContent($element));
    }

    /**
     * Build element attributes
     *
     * @return array<string, mixed>
     */
    protected function buildElementAttributes(Element|RootElement $element): array
    {
        $attributes = $element->getAttributes();

        if (empty($attributes)) {
            return [];
        }

        // Now we'll return the `_attributes` key in the array which the
        // Spatie Array to XML engine will recognise.

        return ['_attributes' => $attributes];
    }

    /**
     * Build element content
     *
     * @return array<string, mixed>
     */
    protected function buildElementContent(Element $element): array
    {
        $output = [];

        // Now we'll build up the content if the content is scalar (like
        // a string, int, float or bool) we'll use the `_value` property
        // on the array.

        $content = $element->getContent();

        if (is_scalar($content)) {
            $output['_value'] = (string)$content;
        }

        // If the content is an array we need to do some fancier logic
        // to make sure nested objects are accounted for.

        if (is_array($content)) {
            // We'll walk through the array recursively and build up the element's data

            $content = $this->convertXmlContentIntoArray($content);

            $output = array_merge($output, $content);
        }

        return $output;
    }

    /**
     * Convert XML content into array
     *
     * @param array<string, Element|CDATA|callable|array<mixed>> $content
     * @return array<string, mixed>
     */
    protected function convertXmlContentIntoArray(array $content = []): array
    {
        $arrayContent = [];

        foreach ($content as $key => $value) {
            if ($value instanceof Element) {
                $value = $this->convertElementIntoArray($value);
            }

            if ($value instanceof CDATA) {
                $value = ['_cdata' => $value->getContent()];
            }

            if (is_array($value)) {
                $value = $this->convertXmlContentIntoArray($value);
            }

            if ($value instanceof Closure) {
                $value = function () use ($value) {
                    return $this->convertElementIntoArray(new Element($value()));
                };
            }

            $arrayContent[$key] = $value;
        }

        return $arrayContent;
    }

    /**
     * Set the XML encoding
     */
    public function setXmlEncoding(string $xmlEncoding): static
    {
        $this->xmlEncoding = $xmlEncoding;

        return $this;
    }

    /**
     * Set the XML version
     */
    public function setXmlVersion(string $xmlVersion): static
    {
        $this->xmlVersion = $xmlVersion;

        return $this;
    }

    /**
     * Set the XML standalone
     */
    public function setXmlStandalone(bool $xmlStandalone): static
    {
        $this->xmlStandalone = $xmlStandalone;

        return $this;
    }

    /**
     * Add processing instruction to the XML
     *
     * @return $this
     */
    public function addProcessingInstruction(string $target, string $data): static
    {
        $this->processingInstructions[$target] = $data;

        return $this;
    }
}
