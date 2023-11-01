<div align="center">

## 🌵 XML Wrangler - Easily Read & Write XML in PHP

</div>

XML Wrangler is a minimalist PHP library designed to make reading and writing XML easy. XML Wrangler has been built with developer experience in mind - you can read any type of XML file, even with complex namespaces and even large XML files. It will also throw exceptions if the XML is invalid!

You can use the table of contents button in the top left to find a section quickly.

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

The `value` and `element` methods use a memory efficient search too so it can handle large XML files.

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

$reader->value('food.0'); // ['name' => 'Belgian Waffles', 'price' => '$5.95', ...]

// Use the element method to get a simple Element DTO containing attributes and content

$reader->element('food.0'); // Element::class

// Use XPath to query the XML

$reader->xpathValue('//food[@bestSeller="true"]/name'); // ['Belgian Waffles', 'Berry-Berry Belgian Waffles']
```

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
## Documentation
### Reading XML
This section on the documentation is for using the XML reader.
#### Various Input Types Supported
The XML reader can accept a variety of input types to make your life easier. You can use an XML string, file, or provide a resource. You can also read the XML directly from a PSR response (like Guzzle) or [Saloon](https://github.com/saloonphp/saloon) response.
```php
use Saloon\XmlWrangler\XmlReader;

$reader = XmlReader::fromString('<?xml version="1.0" encoding="utf-8"?><breakfast_menu>...');
$reader = XmlReader::fromFile('path/to/file.xml');
$reader = XmlReader::fromStream(fopen('path/to/file.xml', 'rb');
$reader = XmlReader::fromPsrResponse($response);
$reader = XmlReader::fromSaloonResponse($response);
```
> **Warning**
> Due to limitations of the underlying PHP XMLReader class, the `fromStream`, `fromPsrResponse` and `fromSaloon` methods will create a temporary file on your machine/server to read from which will be automatically removed
> when the reader is destructed. You will need to ensure that you have enough storage on your machine to use this method.

#### Converting Everything Into An Array
You can use the `elements` and `values` methods to convert the whole XML document into an array. If you would like an array of values, use the `values` method - but if you need to access attributes on the elements, the `elements` method will return an array of `Element` DTOs.
```php
$reader = XmlReader::fromString(...);

$elements = $reader->elements(); // Array of `Element::class` DTOs

$values = $reader->values(); // Array of values.
```
If you are reading a large XML file, you should use the `asGenerator` argument. This will return a generator which can be iterated over only keeping one element in memory at a time.
```php
$elements = $reader->elements(asGenerator: true);

foreach ($elements as $element) {
    // Only one element in memory...
}

//

$values = $reader->values(asGenerator: true);

foreach ($values as $value) {
    // Only one value in memory...
}
```

#### Reading Specific Values
You can use the `value` method to get a specific element's value. You can use dot-notation to search for child elements. You can also use whole numbers to find specific positions of multiple elements. This method searches through the whole XML body in a memory efficient way.

This method will return a single value if there is one element or an array of values if it has found multiple elements.
```php
$reader = XmlReader::fromString('
    <?xml version="1.0" encoding="utf-8"?>
    <person>
        <name>Sammyjo20</name>
        <favourite-songs>
            <song>Luke Combs - When It Rains It Pours</song>
            <song>Sam Ryder - SPACE MAN</song>
            <song>London Symfony Orchestra - Starfield Suite</song>
        </favourite-songs>
    </person>
');

$reader->value('name') // 'Sammyjo20'

$reader->value('song'); // ['Luke Combs - When It Rains It Pours', 'Sam Ryder - SPACE MAN', ...]

$reader->value('song.2'); // 'London Symfony Orchestra - Starfield Suite'
```
#### Reading Specific Values via XPath
You can use the `xpathValue` method to find a specific element's value with an [XPath](https://devhints.io/xpath) query.
```php
<?php

$reader = XmlReader::fromString(...);

$reader->xpathValue('//person/favourite-songs/song[3]'); //  Element('London Symfony Orchestra - Starfield Suite')
```
>**Warning**
>This method is not memory safe as XPath requires all the XML to be loaded in memory at once.
#### Reading Specific Elements
You can use the `element` method to search for a specific element. You can use dot-notation to search for child elements. You can also use whole numbers to find specific positions of multiple elements. This method searches through the whole XML body in a memory efficient way.

This method will return an `Element` DTO if there is one element or an array of elements if it has found multiple.
```php
$reader = XmlReader::fromString('
    <?xml version="1.0" encoding="utf-8"?>
    <person>
        <name>Sammyjo20</name>
        <favourite-songs>
            <song>Luke Combs - When It Rains It Pours</song>
            <song>Sam Ryder - SPACE MAN</song>
            <song>London Symfony Orchestra - Starfield Suite</song>
        </favourite-songs>
    </person>
');

$reader->element('name') // Element('Sammyjo20')

$reader->element('song'); // [Element('Luke Combs - When It Rains It Pours'), Element('Sam Ryder - SPACE MAN'), ...]

$reader->element('song.2'); // Element('London Symfony Orchestra - Starfield Suite')
```
#### Reading Specific Elements via XPath
You can use the `xpathElement` method to find a specific element with an [XPath](https://devhints.io/xpath) query.
```php
<?php

$reader = XmlReader::fromString(...);

$reader->xpathElement('//person/favourite-songs/song[3]'); //  Element('London Symfony Orchestra - Starfield Suite')
```
>**Warning**
>This method is not memory safe as XPath requires all the XML to be loaded in memory at once.
#### Nullable Methods
By default, the `element`, `xpathElement`, `value` and `xpathValue` methods will throw an exception if a given search string could find results. If you would like these to be nullable you can use the `nullable` argument.
```php
$name = $reader->element('name', nullable: true);
```

#### Using Generators
When searching a large file, you can use the `asGenerator` argument which will always return a generator of results only keeping one item in memory at a time.
```php
$names = $reader->element('name', asGenerator: true);

foreach ($names as $name) {
    //
}
```

### Writing XML
This section on the documentation is for using the XML writer.
#### Basic Usage
#### Using the Element DTO
#### Arrays Of Values
#### CDATA Element
#### Composable Elements
Sometimes you might have a part of XML that you will reuse across multiple XML requests in your application. With XML Wrangler, you can create "composable" elements
where you can define your XML content in a class which you can re-use across your application. Extend the `Element` class and use the
protected static `compose` method.

```php
<?php

use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\Element;

class BelgianWafflesElement extends Element
{
    protected function compose(): void
    {
        $this
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
    'food' => new BelgianWafflesElement,
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
#### Customising XML encoding and version
#### Adding custom "processing instructions" to the XML
#### Minification

## Credits
XML Wrangler is a simple wrapper around two really powerful libraries which do a lot of the legwork. Both of libraries are fantastic and deserve a star!

- [veewee/xml](https://github.com/veewee/xml) - Used for reading and decoding XML, but has a powerful writing engine of its own.
- [spatie/array-to-xml](https://github.com/spatie/array-to-xml) - A brilliant library to convert an array into XML

### Other Credits

- [Sam Carré](https://github.com/sammyjo20)
- [Toon Verwerft](https://github.com/veewee)
- [Spatie](https://github.com/spatie)
