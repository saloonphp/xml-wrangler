<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Exceptions;

class MultipleNodesFoundException extends XmlReaderException
{
    /**
     * Constructor
     */
    public function __construct(string $searchTerm)
    {
        parent::__construct(sprintf('Multiple nodes found for [%s]', $searchTerm));
    }
}
