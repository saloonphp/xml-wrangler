<div align="center">

## 🌵 XML Wrangler - Easily Read & Write XML in PHP

</div>

XML Wrangler is a minimalist PHP library designed to make reading and writing XML easy. XML Wrangler has been built with developer experience in mind. You can read any type of XML file, even with complex namespaces and even large XML files. It will also throw exceptions if the XML is invalid!

## Installation
XML Wrangler is installed via Composer.

```
composer require saloonphp/xml-wrangler
```
> Supports PHP 8.1+

## Reading XML
Reading XML can be done by simply passing the XML string or file into the XML reader and using one of the many methods to search and find a specific element. 
You can also convert every element into an easily traversable array. If you need to access attributes on an element you can use
the `Element` DTO which is a simple class to access the content and attributes. *No more dealing with clunky DOMElement classes!*

The `value` and `element` method use a memory efficient search too so it can handle large XML files.

```xml
<breakfast_menu>
  <food soldOut="false" bestSeller="true">
    <name>Belgian Waffles</name>
    <price>$5.95</price>
    <description>Two of our famous Belgian Waffles with plenty of real maple syrup</description>
    <calories>650</calories>
  </food>
  <food soldOut="false" bestSeller="false">
    <name>Strawberry Belgian Waffles</name>
    <price>$7.95</price>
    <description>Light Belgian waffles covered with strawberries and whipped cream</description>
    <calories>900</calories>
  </food>
  <food soldOut="false" bestSeller="true">
    <name>Berry-Berry Belgian Waffles</name>
    <price>$8.95</price>
    <description>Light Belgian waffles covered with an assortment of fresh berries and whipped cream</description>
    <calories>900</calories>
  </food>
</breakfast_menu>
```



```php
<?php

use Saloon\XmlWrangler\XmlReader;

$reader = XmlReader::fromString($xml);

// Retrieve all values as one simple array

$reader->values(); // ['breakfast_menu' => [['name' => '...'], ['name' => '...'], ['name' => '...']]

// Use dot-notation to find a specific element

$reader->value('breakfast_menu.food.0'); // ['name' => 'Belgian Waffles', 'price' => '$5.95', ...]

// Use the element method to get a simple Element DTO containing attributes and content

$reader->element('breakfast_menu.food.0'); // Element::class

// Use XPath to query the XML

$reader->xpathValue('//breakfast_menu/food[@bestSeller="true"]/name'); // ['Belgian Waffles', 'Berry-Berry Belgian Waffles']
```

> **Note**
> Full documentation on the XML reader can be found below.

## Writing XML
Writing XML is as simple as defining a PHP array and using keys and values to define elements. When you need to define elements with a few more characteristics like attributes or namespaces you can use the `Element` DTO to define more advanced elements.

```php
<?php

use Saloon\XmlWrangler\Data\Element;
use Saloon\XmlWrangler\XmlWriter;

$writer = new XmlWriter;

$xml = $writer->write('breakfast_menu', [
    'food' => [
        [
            'name' => 'Belgian Waffles',
            'price' => '$5.95',
            'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
            'calories' => '650',
        ],
        [
            'name' => 'Strawberry Belgian Waffles',
            'price' => '$7.95',
            'description' => 'Light Belgian waffles covered with strawberries and whipped cream',
            'calories' => '900',
        ],
        
        // You can also use the Element class if you need to define elements with
        // namespaces or with attributes.
        
        Element::make([
            'name' => 'Berry-Berry Belgian Waffles',
            'price' => '$8.95',
            'description' => 'Light Belgian waffles covered with an assortment of fresh berries and whipped cream',
            'calories' => '900',
        ])->setAttributes(['bestSeller' => 'true']),
    ],
]);
```
The above code will create the following XML
```xml
<?xml version="1.0" encoding="utf-8"?>
<breakfast_menu>
  <food>
    <name>Belgian Waffles</name>
    <price>$5.95</price>
    <description>Two of our famous Belgian Waffles with plenty of real maple syrup</description>
    <calories>650</calories>
  </food>
  <food>
    <name>Strawberry Belgian Waffles</name>
    <price>$7.95</price>
    <description>Light Belgian waffles covered with strawberries and whipped cream</description>
    <calories>900</calories>
  </food>
  <food bestSeller="true">
    <name>Berry-Berry Belgian Waffles</name>
    <price>$8.95</price>
    <description>Light Belgian waffles covered with an assortment of fresh berries and whipped cream</description>
    <calories>900</calories>
  </food>
</breakfast_menu>
```
> **Note**
> Full documentation on the XML reader can be found below.

## Documentation
### Reading XML
### Writing XML
#### Composable Elements
Sometimes you might have a part of XML that you will reuse across multiple XML requests in your application. With XML Wrangler, you can create "composable" elements
where you can define your XML content in a class which you can re-use across your application. Extend the `Element` class and use the
protected static `compose` method.

```php
<?php

use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\Element;

class BelgianWaffleElement extends Element
{
    /**
     * Compose your own element
     */
    protected static function compose(Element $element): void
    {
        $element
            ->setAttributes([
                'soldOut' => 'false',
                'bestSeller' => 'true',
            ])
            ->setContent([
                'name' => 'Belgian Waffles',
                'price' => '$5.95',
                'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
                'calories' => '650',
            ]);
    }
}

$writer = XmlWriter::make()->write('root', [
    'food' => new BelgianWaffleElement,
]);
```

This will result in XML like this:

```xml
<?xml version="1.0" encoding="utf-8"?>
<breakfast_menu>
    <food soldOut="false" bestSeller="true">
        <name>Belgian Waffles</name>
        <price>$5.95</price>
        <description>Two of our famous Belgian Waffles with plenty of real maple syrup</description>
        <calories>650</calories>
    </food>
</breakfast_menu>
```

## Credits
XML Wrangler is a simple wrapper around two really powerful libraries which do all the legwork. These two libraries each have their own ways of
handling XML and certainly deserve a star.

- [veewee/xml](https://github.com/veewee/xml) - Used for reading and decoding XML, but has a powerful writing engine of its own.
- [spatie/array-to-xml](https://github.com/spatie/array-to-xml) - A brilliant library to convert an array into XML

### Other Credits

- [Toon Verwerft](https://github.com/veewee)
- [Spatie](https://github.com/spatie)
- [Sam Carré](https://github.com/sammyjo20)
