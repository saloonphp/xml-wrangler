<?php

declare(strict_types=1);

namespace Saloon\XmlWrangler\Traits;

use Saloon\XmlWrangler\Data\Element;

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

    /**
     * Recursively convert element into values
     */
    public function values(): array|string
    {
        $content = $this->getContent();

        if (is_array($content)) {
            foreach ($content as $key => $value) {
                if ($value instanceof Element) {
                    $content[$key] = $value->values();
                }
            }
        }

        return $content;
    }
}
