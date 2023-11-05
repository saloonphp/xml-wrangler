<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Reader\Visitors;

use \DOMNode;
use VeeWee\Xml\Dom\Traverser\Action;
use function VeeWee\Xml\Dom\Predicate\is_element;
use VeeWee\Xml\Dom\Traverser\Visitor\AbstractVisitor;
use function VeeWee\Xml\Dom\Locator\Attribute\xmlns_attributes_list;

class RemoveRootNamespace extends AbstractVisitor
{
    public function onNodeLeave(DOMNode $node): Action
    {
        if (! is_element($node)) {
            return new Action\Noop();
        }

        $namespaces = xmlns_attributes_list($node);

        foreach ($namespaces as $namespace) {
            if ($namespace->nodeName === 'xmlns') {
                $node->removeAttributeNS($namespace->namespaceURI, $namespace->prefix);
            }
        }

        return new Action\Noop();
    }
}
