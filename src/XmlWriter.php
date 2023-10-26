<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Saloon\XmlWrangler\Exceptions\XmlWriterException;
use Spatie\ArrayToXml\ArrayToXml;
use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\Data\RootElement;

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
     * Constructor
     */
    public function __construct(string $xmlEncoding = 'utf-8', string $xmlVersion = '1.0')
    {
        $this->xmlEncoding = $xmlEncoding;
        $this->xmlVersion = $xmlVersion;
    }

    /**
     * Build the XML body
     *
     * @param array $content
     * @param bool $minified
     * @return string
     * @throws \DOMException
     * @throws \Saloon\XmlWrangler\Exceptions\XmlWriterException
     */
    public function write(array $content, bool $minified = false): string
    {
        if (empty($content)) {
            throw new XmlWriterException('You must specify at least one element in the XML.');
        }

        if (count($content) > 1) {
            throw new XmlWriterException('You must only have one root element.');
        }

        $rootElementName = array_key_first($content);

        if (! is_string($rootElementName)) {
            throw new XmlWriterException('The root element key must be a string.');
        }

        $rootElement = [
            'rootElementName' => $rootElementName,
        ];

        $rootElementContent = $content[$rootElementName];

        // When the root element content is an element we should check for
        // any attributes that might be on the element.

        if ($rootElementContent instanceof Element) {
            $attributes = $rootElementContent->getAttributes();

            if (! empty($attributes)) {
                $rootElement['_attributes'] = $attributes;
            }

            // Now we'll get the content of the XML body.

            $content = $rootElementContent->getContent();
        } else {
            $content = $content[$rootElementName];
        }

        // When the content is not an array, we'll convert the scalar value
        // into an array with _value which will insert the value in the
        // node as text.

        if (isset($content) && ! is_array($content)) {
            $content = ['_value' => $content];
        }

        // Now we will convert the XML content into an array which will recursively
        // convert all the elements into their correct format.

        $content = static::convertXmlContentIntoArray($content ?? []);

        $engine = new ArrayToXml($content, $rootElement, xmlEncoding: $this->xmlEncoding, xmlVersion: $this->xmlVersion);

        if ($minified === false) {
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
