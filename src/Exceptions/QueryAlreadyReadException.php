<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Exceptions;

class QueryAlreadyReadException extends XmlReaderException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('The underlying generator on this query instance has already been used.');
    }
}
