<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Exceptions;

class MissingNodeException extends XmlReaderException
{
    /**
     * Constructor
     */
    public function __construct(string $searchTerm)
    {
        parent::__construct(sprintf('Unable to find the [%s] node', $searchTerm));
    }
}
