<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Generator;
use Illuminate\Support\Collection;
use Saloon\XmlWrangler\Exceptions\MissingNodeException;
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
     */
    public function get(): array
    {
        return iterator_to_array($this->data);
    }

    /**
     * Return the node as a collection
     *
     * Requires illuminate/support
     *
     * @return Collection<string, mixed>
     */
    public function collect(): Collection
    {
        return Collection::make($this->get());
    }

    /**
     * Retrieve the first value in the node
     */
    public function first(): mixed
    {
        foreach ($this->data as $datum) {
            return $datum;
        }

        return null;
    }

    /**
     * Retrieve the first value in the node or fail
     *
     * @throws \Saloon\XmlWrangler\Exceptions\MissingNodeException
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
     */
    public function sole(): mixed
    {
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
}
