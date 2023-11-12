<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler;

use Generator;
use Illuminate\Support\LazyCollection;

/**
 * @template TReturnType
 *
 * @extends \Saloon\XmlWrangler\Query<TReturnType>
 */
class LazyQuery extends Query
{
    /**
     * Return the node as a generator
     *
     * Useful when reading very large XML files
     *
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function lazy(): Generator
    {
        $this->throwOnInvalidGenerator();

        return $this->data;
    }

    /**
     * Return the node as a lazy collection
     *
     * Useful when reading very large XML files
     *
     * Requires illuminate/support
     *
     * @return LazyCollection<int, TReturnType>
     * @throws \Saloon\XmlWrangler\Exceptions\QueryAlreadyReadException
     */
    public function collectLazy(): LazyCollection
    {
        $this->throwOnInvalidGenerator();

        /** @phpstan-ignore-next-line */
        return LazyCollection::make(fn () => yield from $this->data);
    }
}
