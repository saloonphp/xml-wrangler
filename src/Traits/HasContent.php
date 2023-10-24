<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Traits;

trait HasContent
{
    /**
     * Content
     *
     * @var mixed|null
     */
    protected mixed $content = null;

    /**
     * Set content on the tag
     *
     * @return $this
     */
    public function setContent(mixed $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the content
     */
    public function getContent(): mixed
    {
        return $this->content;
    }
}
