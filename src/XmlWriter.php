<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Spatie\ArrayToXml\ArrayToXml;
use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\Data\RootElement;

class XmlWriter
{
    /**
     * Root element of the XML builder
     */
    protected RootElement $rootElement;

    /**
     * Content of the XML
     */
    protected array $content = [];

    /**
     * XML Encoding
     */
    protected string $xmlEncoding;

    /**
     * XML version
     */
    protected string $xmlVersion;

    /**
     * Constructor
     */
    public function __construct(RootElement $rootElement = null, array $content = [], string $xmlEncoding = 'utf-8', string $xmlVersion = '1.0')
    {
        $this->rootElement = $rootElement ?? new RootElement('root');
        $this->content = $content;
        $this->xmlEncoding = $xmlEncoding;
        $this->xmlVersion = $xmlVersion;
    }

    /**
     * Build the XML body
     *
     * @throws \DOMException
     */
    public function write(array $additionalContent = [], bool $minify = false): string
    {
        $rootElement = $this->rootElement;
        $baseRootElement = $rootElement->toElement();

        // We have to convert the root element content because there's a chance that it
        // could be just a string, so we need to convert this.

        $rootElementContent = static::buildElementContent($baseRootElement);

        $rootElementArray = [
            'rootElementName' => $rootElement->getName(),
            ...static::buildElementAttributes($baseRootElement),
        ];

        // Building the root element

        // Merge each of the different content types together

        $content = array_merge($rootElementContent, $this->content, $additionalContent);

        // Now we'll convert the content into an array that our engine will accept

        $content = static::convertXmlContentIntoArray($content);

        $engine = new ArrayToXml($content, $rootElementArray, xmlEncoding: $this->xmlEncoding, xmlVersion: $this->xmlVersion);

        if ($minify === false) {
            $engine->prettify();
        }

        return $engine->toXml();
    }

    /**
     * Convert an element into an array
     */
    public static function convertElementIntoArray(Element $element): array
    {
        return array_merge(static::buildElementAttributes($element), static::buildElementContent($element));
    }

    /**
     * Build element attributes
     */
    protected static function buildElementAttributes(Element $element): array
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
     */
    protected static function buildElementContent(Element $element): array
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

            $content = static::convertXmlContentIntoArray($content);

            $output = array_merge($output, $content);
        }

        return $output;
    }

    /**
     * Convert XML content into array
     */
    protected static function convertXmlContentIntoArray(array $content = []): array
    {
        $arrayContent = [];

        foreach ($content as $key => $value) {
            if ($value instanceof Element) {
                $value = static::convertElementIntoArray($value);
            }

            if (is_array($value)) {
                $value = static::convertXmlContentIntoArray($value);
            }

            $arrayContent[$key] = $value;
        }

        return $arrayContent;
    }

    /**
     * Set the root element
     *
     * @return $this
     */
    public function setRootElement(RootElement $rootElement): static
    {
        $this->rootElement = $rootElement;

        return $this;
    }

    /**
     * Set the XML encoding
     */
    public function setXmlEncoding(string $xmlEncoding): XmlWriter
    {
        $this->xmlEncoding = $xmlEncoding;

        return $this;
    }

    /**
     * Set the XML version
     */
    public function setXmlVersion(string $xmlVersion): XmlWriter
    {
        $this->xmlVersion = $xmlVersion;

        return $this;
    }
}
