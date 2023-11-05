<div align="center">

## 🌵 XML Wrangler - Easily Read & Write XML in PHP

</div>

XML Wrangler is a simplistic PHP library designed to make reading and writing XML easy. XML Wrangler has been built with developer experience in mind - you can read any type of XML file, even with complex namespaces and even large XML files. It will also throw exceptions if the XML is invalid!

## Installation
XML Wrangler is installed via Composer.

```
composer require saloonphp/xml-wrangler
```
> Requires PHP 8.1+

## Reading XML
Reading XML can be done by passing the XML string or file into the XML reader and using one of the many methods to search and find a specific element or value. 
You can also convert every element into an easily traversable array if you prefer. If you need to access attributes on an element you can use
the `Element` DTO which is a simple class to access the content and attributes. XML Wrangler provides methods to iterate through multiple elements while only
keeping one element in memory at a time.

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

$reader->value('food.0')->sole(); // ['name' => 'Belgian Waffles', 'price' => '$5.95', ...]

// Use the element method to get a simple Element DTO containing attributes and content

$reader->element('food.0')->sole(); // Element::class

// Use XPath to query the XML

$reader->xpathValue('//food[@bestSeller="true"]/name')->get(); // ['Belgian Waffles', 'Berry-Berry Belgian Waffles']
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
The XML reader can accept a variety of input types. You can use an XML string, file, or provide a resource. You can also read the XML directly from a PSR response (like from [Guzzle](https://github.com/guzzle/guzzle)) or a [Saloon](https://github.com/saloonphp/saloon) response.
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
> **Note**
> If you are reading a large XML file, you should use the `element` or `value` methods instead. These methods can iterate through large XML files without running out of memory.

#### Reading Specific Values
You can use the `value` method to get a specific element's value. You can use dot-notation to search for child elements. You can also use whole numbers to find specific positions of multiple elements. This method searches through the whole XML body in a memory-efficient way.

This method will return a `LazyQuery` class which has different methods on to retrieve the data.
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

$reader->value('person.name')->sole() // 'Sammyjo20'

$reader->value('song')->get(); // ['Luke Combs - When It Rains It Pours', 'Sam Ryder - SPACE MAN', ...]

$reader->value('song.2')->sole(); // 'London Symfony Orchestra - Starfield Suite'
```
#### Reading Specific Elements
You can use the `element` method to search for a specific element. This method will provide an `Element` class which contains the value and attributes. You can use dot-notation to search for child elements. You can also use whole numbers to find specific positions of multiple elements. This method searches through the whole XML body in a memory efficient way.

This method will return a `LazyQuery` class which has different methods to retrieve the data.
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

$reader->element('name')->sole(); // Element('Sammyjo20')

$reader->element('song')->get(); // [Element('Luke Combs - When It Rains It Pours'), Element('Sam Ryder - SPACE MAN'), ...]

$reader->element('song.2')->sole(); // Element('London Symfony Orchestra - Starfield Suite')
```
#### Lazily Iterating
When searching a large file, you can use the `lazy` or `collectLazy` methods which will return a generator of results only keeping one item in memory at a time.
```php
$names = $reader->element('name')->lazy();

foreach ($names as $name) {
    //
}
```
#### Using Laravel Collections
If you are using Laravel, you can use the `collect` and `collectLazy` methods which will convert the elements into a Laravel Collection/Lazy Collection. If you are not using Laravel, you can install the `illuminate/collections` package via Composer to add this functionality.
```php
$names = $reader->value('name')->collect();

$names = $reader->value('name')->collectLazy();
```
#### Searching by specific attributes
Sometimes you might want to search for a specific element or value where the element contains a specific attribute. You can do this by providing a second argument to the `value` or `element` method. This will search the last element for the attributes and will return if they match.
```php
$reader = XmlReader::fromString('
    <?xml version="1.0" encoding="utf-8"?>
    <person>
        <name>Sammyjo20</name>
        <favourite-songs>
            <song>Luke Combs - When It Rains It Pours</song>
            <song>Sam Ryder - SPACE MAN</song>
            <song recent="true">London Symfony Orchestra - Starfield Suite</song>
        </favourite-songs>
    </person>
');

$reader->element('song', ['recent' => 'true'])->sole(); // Element('London Symfony Orchestra - Starfield Suite')

$reader->value('song', ['recent' => 'true'])->sole(); // 'London Symfony Orchestra - Starfield Suite'
```
### Reading with XPath
XPath is a fantastic way to search through XML. With one string, you can search for a specific element, with specific attributes or indexes. If you are interested in learning XPath, you can [click here for a useful cheatsheet](https://devhints.io/xpath).

#### Reading Specific Values via XPath
You can use the `xpathValue` method to find a specific element's value with an XPath query. This method will return a `Query` class which has different methods to retrieve the data.
```php
<?php
$reader = XmlReader::fromString(...);

$reader->xpathValue('//person/favourite-songs/song[3]')->sole(); //  'London Symfony Orchestra - Starfield Suite'
```
#### Reading Specific Elements via XPath
You can use the `xpathElement` method to find a specific element with an XPath query. This method will return a `Query` class which has different methods to retrieve the data.
```php
<?php

$reader = XmlReader::fromString(...);

$reader->xpathElement('//person/favourite-songs/song[3]')->sole(); //  Element('London Symfony Orchestra - Starfield Suite')
```
>**Warning**
>Due to limitations with XPath - the above methods used to query with XPath are not memory safe and may not be suitable for large XML documents.
#### XPath and un-prefixed namespaces
You might found yourself with an XML document that contains an un-prefixed `xmlns` attribute - like this:
```xml
<container xmlns="http://example.com/xml-wrangler/person" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" />
```
When this happens, XML Wrangler will automatically remove these un-prefixed namespaces to improve compatability. If you would like to keep these namespaces, you can use `setXpathNamespaceMap` to map each un-prefixed XML namespace.
```php
$reader = XmlReader::fromString(...);
$reader->setXpathNamespaceMap([
    'root' => 'http://example.com/xml-wrangler/person',
]);

$reader->xpathValue('//root:person/root:favourite-songs/root:song[3]')->sole();
```
### Writing XML
This section on the documentation is for using the XML writer.
#### Basic Usage
The most basic usage of the reader is to use string keys for the element names and values for the values of the element. The writer accepts infinitely nested arrays for nested elements.
```php
use Saloon\XmlWrangler\XmlWriter;

$xml = XmlWriter::make()->write('root', [
    'name' => 'Sam',
    'twitter' => '@carre_sam',
    'facts' => [
        'favourite-song' => 'Luke Combs - When It Rains It Pours'
    ],
]);
```
The above code will be converted into the following XML
```xml
<?xml version="1.0" encoding="utf-8"?>
<root>
  <name>Sam</name>
  <twitter>@carre_sam</twitter>
  <facts>
    <favourite-song>Luke Combs - When It Rains It Pours</favourite-song>
  </facts>
</root>
```
#### Using the Element DTO
When writing XML, you will often need to define attributes and namespaces on your elements. You can use the `Element` class in the array of XML to add an element with an attribute or namespace. You can mix the `Element` class with other arrays and string values.

```php
use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\Element;

$xml = XmlWriter::make()->write('root', [
    'name' => 'Sam',
    'twitter' => Element::make('@carre_sam')->addAttribute('url', 'https://twitter.com/@carre_sam'),
    'facts' => [
        'favourite-song' => 'Luke Combs - When It Rains It Pours'
    ],
    'soap:custom-namespace' => Element::make()->addNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope'),
]);
```
This will result in the following XML
```xml
<?xml version="1.0" encoding="utf-8"?>
<root>
  <name>Sam</name>
  <twitter url="https://twitter.com/@carre_sam">@carre_sam</twitter>
  <facts>
    <favourite-song>Luke Combs - When It Rains It Pours</favourite-song>
  </facts>
  <soap:custom-namespace xmlns:soap="http://www.w3.org/2003/05/soap-envelope"/>
</root>
```
#### Arrays Of Values
You will often need to define an array of elements. You can do this by simply providing an array of values or element classes.
```php
use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\Element;

$xml = XmlWriter::make()->write('root', [
    'name' => 'Luke Combs',
    'songs' => [
        'song' => [
            'Fast Car',
            'The Kind Of Love We Make',
            'Beautiful Crazy',
            Element::make('She Got The Best Of Me')->addAttribute('hit', 'true'),
        ],
    ],
]);
```
This will result in the following XML
```xml
<?xml version="1.0" encoding="utf-8"?>
<root>
  <name>Luke Combs</name>
  <songs>
    <song>Fast Car</song>
    <song>The Kind Of Love We Make</song>
    <song>Beautiful Crazy</song>
    <song hit="true">She Got The Best Of Me</song>
  </songs>
</root>
```
#### Customising the root element
Sometimes you may need to change the name of the root element. This can be customised as the first argument of the `write` method.

```php
$xml = XmlWriter::make()->write('custom-root', [...])
```
If you would like to add attributes and namespaces to the root element you can use a `RootElement` class here too.

```php
use Saloon\XmlWrangler\Data\RootElement;

$rootElement = RootElement::make('root')->addNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');

$xml = XmlWriter::make()->write($rootElement, [...])
```
#### CDATA Element
If you need to add a CDATA tag you can use the `CDATA` class.

```php
use Saloon\XmlWrangler\Data\CDATA;use Saloon\XmlWrangler\XmlWriter;
use Saloon\XmlWrangler\Data\Element;

$xml = XmlWriter::make()->write('root', [
    'name' => 'Sam',
    'custom' => CDATA::make('Here is some CDATA content!'),
]);
```
This will result in the following XML
```xml
<?xml version="1.0" encoding="utf-8"?>
<root>
  <name>Sam</name>
  <custom><![CDATA[Here is some CDATA content!]]></custom>
</root>
```
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
The default XML encoding is `UTF-8` and the default version of XML is `1.0` if you would like to customise this you can with the `setXmlEncoding` and `setXmlVersion` methods on the writer.
```php
use Saloon\XmlWrangler\XmlWriter;

$writer = new XmlWriter();

$writer->setXmlEncoding('ISO-8859-1');
$writer->setXmlVersion('2.0');

// $writer->write(...);
```
#### Adding custom "Processing Instructions" to the XML
You can add a custom "Processing Instruction" to the XML by using the `addProcessingInstruction` method.

```php
use Saloon\XmlWrangler\XmlWriter;

$writer = new XmlWriter();
$writer->addProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="base.xsl"');

$xml = $writer->write('root', ['name' => 'Sam']);
```
This will result in the following XML
```xml
<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="base.xsl"?>
<root>
  <name>Sam</name>
</root>
```

#### Minification
By default the XML written is not minified. You can provide the third argument to the `write` method to minify the XML.
```php
use Saloon\XmlWrangler\XmlWriter;

$xml = XmlWriter::make()->write('root', [...], minified: true);
```
## Credits
XML Wrangler is a simple wrapper around two really powerful libraries which do a lot of the legwork. Both of libraries are fantastic and deserve a star!

- [veewee/xml](https://github.com/veewee/xml) - Used for reading and decoding XML, but has a powerful writing engine of its own.
- [spatie/array-to-xml](https://github.com/spatie/array-to-xml) - A brilliant library to convert an array into XML

### Other Credits

- [Sam Carré](https://github.com/sammyjo20)
- [Toon Verwerft](https://github.com/veewee)
- [Spatie](https://github.com/spatie)
