<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class LazyQuery extends Query
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
     * Return the node as a generator
     *
     * Useful when reading very large XML files
     */
    public function lazy(): Generator
    {
        return $this->data;
    }

    /**
     * Return the node as a lazy collection
     *
     * Useful when reading very large XML files
     *
     * Requires illuminate/support
     *
     * @return LazyCollection<int, mixed>
     */
    public function collectLazy(): LazyCollection
    {
        /** @phpstan-ignore-next-line */
        return LazyCollection::make(fn () => yield from $this->data);
    }
}
