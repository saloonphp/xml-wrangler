<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Data;

class CDATA
{
    /**
     * Content
     */
    protected ?string $content = null;

    /**
     * Constructor
     *
     * Base XML Element DTO
     */
    public function __construct(string $content = null)
    {
        $this->setContent($content);
    }

    /**
     * Create an element instance
     */
    public static function make(string $content = null): static
    {
        return new static($content);
    }

    /**
     * Set content on the tag
     *
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the content
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
