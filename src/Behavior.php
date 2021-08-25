<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\HtmlSanitizer;

use LogicException;
use TYPO3\HtmlSanitizer\Behavior\Tag;

/**
 * Declares behavior used by node visitors
 * (and any component used during sanitization)
 */
class Behavior
{
    /**
     * not having any behavioral capabilities
     */
    public const BLUNT = 0;

    /**
     * in case an unexpected tag was found, encode the whole tag as HTML
     */
    public const ENCODE_INVALID_TAG = 1;

    /**
     * in case an unexpected attribute was found, encode the whole tag as HTML
     */
    public const ENCODE_INVALID_ATTR = 2;

    /**
     * remove children at tags that did not expect children
     */
    public const REMOVE_UNEXPECTED_CHILDREN = 4;

    /**
     * https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     * custom elements must contain a hyphen (`-`), start with ASCII lower alpha
     */
    public const ALLOW_CUSTOM_ELEMENTS = 8;

    /**
     * @var int
     */
    protected $flags = 0;

    /**
     * @var string
     */
    protected $name = 'undefined';

    /**
     * Tag names as array index, e.g. `['strong' => new Tag('strong')]`
     * @var array<string, Tag>
     */
    protected $tags = [];

    public function withFlags(int $flags): self
    {
        if ($flags === $this->flags) {
            return $this;
        }
        $target = clone $this;
        $target->flags = $flags;
        return $target;
    }

    public function withName(string $name): self
    {
        if ($name === $this->name) {
            return $this;
        }
        $target = clone $this;
        $target->name = $name;
        return $target;
    }

    public function withTags(Tag ...$tags): self
    {
        $names = array_map([$this, 'getTagName'], $tags);
        $this->assertScalarUniqueness($names);
        // uses tag name as array index, e.g. `['strong' => new Tag('strong')]`
        $indexedTags = array_combine($names, $tags);
        if (!is_array($indexedTags)) {
            return $this;
        }
        $this->assertTagUniqueness($indexedTags);
        $target = clone $this;
        $target->tags = array_merge($target->tags, $indexedTags);
        return $target;
    }

    public function withoutTags(Tag ...$tags): self
    {
        $filteredTags = array_filter(
            $this->tags,
            function (Tag $tag) use ($tags) {
                return !in_array($tag, $tags, true);
            }
        );
        if ($filteredTags === $this->tags) {
            return $this;
        }
        $target = clone $this;
        $target->tags = $filteredTags;
        return $target;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string $name
     * @return Tag|null
     */
    public function getTag(string $name): ?Tag
    {
        $name = strtolower($name);
        return $this->tags[$name] ?? null;
    }

    public function shallEncodeInvalidTag(): bool
    {
        return ($this->flags & self::ENCODE_INVALID_TAG) === self::ENCODE_INVALID_TAG;
    }

    public function shallEncodeInvalidAttr(): bool
    {
        return ($this->flags & self::ENCODE_INVALID_ATTR) === self::ENCODE_INVALID_ATTR;
    }

    public function shallRemoveUnexpectedChildren(): bool
    {
        return ($this->flags & self::REMOVE_UNEXPECTED_CHILDREN) === self::REMOVE_UNEXPECTED_CHILDREN;
    }

    public function shallAllowCustomElements(): bool
    {
        return ($this->flags & self::ALLOW_CUSTOM_ELEMENTS) === self::ALLOW_CUSTOM_ELEMENTS;
    }

    /**
     * @param string[] $names
     * @throws LogicException
     */
    protected function assertScalarUniqueness(array $names): void
    {
        $ambiguousNames = array_diff_assoc($names, array_unique($names));
        if ($ambiguousNames !== []) {
            throw new LogicException(
                sprintf(
                    'Ambiguous tag names %s.',
                    implode(', ', $ambiguousNames)
                ),
                1625591503
            );
        }
    }

    /**
     * @param array<string, Tag> $tags
     */
    protected function assertTagUniqueness(array $tags): void
    {
        $existingTagNames = array_intersect_key($this->tags, $tags);
        if ($existingTagNames !== []) {
            throw new LogicException(
                sprintf(
                    'Cannot redeclare tag names %s. Remove duplicates first',
                    implode(', ', array_keys($existingTagNames))
                ),
                1625391217
            );
        }
    }

    protected function getTagName(Tag $tag): string
    {
        return strtolower($tag->getName());
    }
}
