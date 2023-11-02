<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Generator;
use Illuminate\Support\Collection;
use Saloon\XmlWrangler\Exceptions\MissingNodeException;
use Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException;
use Saloon\XmlWrangler\Exceptions\MultipleNodesFoundException;

class Query
{
    /**
     * The search term used for the node
     */
    protected string $searchTerm;

    /**
     * Data source of the node
     */
    protected Generator $data;

    /**
     * Check if we have used the query instance
     */
    protected bool $used = false;

    /**
     * Constructor
     */
    public function __construct(string $searchTerm, Generator $data)
    {
        $this->searchTerm = $searchTerm;
        $this->data = $data;
    }

    /**
     * Return the node as an array
     *
     * @return array<string, mixed>
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function get(): array
    {
        $this->throwOnInvalidGenerator();

        return iterator_to_array($this->data);
    }

    /**
     * Return the node as a collection
     *
     * Requires illuminate/support
     *
     * @return Collection<string, mixed>
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function collect(): Collection
    {
        return Collection::make($this->get());
    }

    /**
     * Retrieve the first value in the node
     *
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function first(): mixed
    {
        $this->throwOnInvalidGenerator();

        foreach ($this->data as $datum) {
            return $datum;
        }

        return null;
    }

    /**
     * Retrieve the first value in the node or fail
     *
     * @throws \Saloon\XmlWrangler\Exceptions\MissingNodeException
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function firstOrFail(): mixed
    {
        return $this->first() ?? throw new MissingNodeException($this->searchTerm);
    }

    /**
     * Retrieve the first value in the node
     *
     * Throws an exception if none exist or more than one exists.
     *
     * @return string|null
     * @throws \Saloon\XmlWrangler\Exceptions\MissingNodeException
     * @throws \Saloon\XmlWrangler\Exceptions\MultipleNodesFoundException
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function sole(): mixed
    {
        $this->throwOnInvalidGenerator();

        $count = 0;
        $result = null;

        foreach ($this->data as $datum) {
            $count++;

            if ($count > 1) {
                throw new MultipleNodesFoundException($this->searchTerm);
            }

            $result = $datum;
        }

        if (is_null($result)) {
            throw new MissingNodeException($this->searchTerm);
        }

        return $result;
    }

    /**
     * Throw an exception if the query has already been read
     *
     * This is a wrapper method instead of getting an exception from the generator.
     *
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    protected function throwOnInvalidGenerator(): void
    {
        if ($this->used === true) {
            throw new QueryAlreadyReadException;
        }

        $this->used = true;
    }
}
