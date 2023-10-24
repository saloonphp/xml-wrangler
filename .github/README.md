<div align="center">

## 🌵 XML Wrangler - Easily Read & Write XML in PHP

</div>

XML Wrangler is a minimalist PHP library designed to make reading and writing XML easy.

XML Wrangler is a simple wrapper around two really powerful libraries which do all the legwork. These two libraries each have their own ways of 
handling XML and certainly deserve a star.

- [veewee/xml](https://github.com/veewee/xml) - Used for reading and decoding XML, but has a powerful writing engine of its own.
- [spatie/array-to-xml](https://github.com/spatie/array-to-xml) - A brilliant library to convert an array into XML

## Reading XML
Reading XML can be done by simply passing the XML string or file into the XML reader and using one of the many methods to search and find a specific element. You can also convert every element into an easily traversable array.

```php
// Example
```

## Writing XML
Writing XML is as simple as defining a PHP array and using keys and values to define elements. When you need to define elements with a few more characteristics like attributes or namespaces you can use the `Element` DTO to define more advanced elements.

```php
Example
```

## Credits
- [Toon Verwerft](https://github.com/veewee)
- [Spatie](https://github.com/spatie)
- [Sam Carré](https://github.com/sammyjo20)
