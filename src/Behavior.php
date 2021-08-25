<?php

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
    const BLUNT = 0;

    /**
     * in case an unexpected tag was found, encode the whole tag as HTML
     */
    const ENCODE_INVALID_TAG = 1;

    /**
     * in case an unexpected attribute was found, encode the whole tag as HTML
     */
    const ENCODE_INVALID_ATTR = 2;

    /**
     * remove children at tags that did not expect children
     */
    const REMOVE_UNEXPECTED_CHILDREN = 4;

    /**
     * https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     * custom elements must contain a hyphen (`-`), start with ASCII lower alpha
     */
    const ALLOW_CUSTOM_ELEMENTS = 8;

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

    /**
     * @param int $flags
     * @return $this
     */
    public function withFlags($flags)
    {
        $flags = (int) $flags;
        if ($flags === $this->flags) {
            return $this;
        }
        $target = clone $this;
        $target->flags = $flags;
        return $target;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function withName($name)
    {
        $name = (string) $name;
        if ($name === $this->name) {
            return $this;
        }
        $target = clone $this;
        $target->name = $name;
        return $target;
    }

    /**
     * @param Tag[] $tags
     * @return $this
     */
    public function withTags(array $tags)
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

    /**
     * @param Tag[] $tags
     * @return $this
     */
    public function withoutTags(array $tags)
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
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $name
     * @return Tag|null
     */
    public function getTag($name)
    {
        $name = (string) $name;
        $name = strtolower($name);
        return isset($this->tags[$name]) ? $this->tags[$name] : null;
    }

    /**
     * @return bool
     */
    public function shallEncodeInvalidTag()
    {
        return ($this->flags & self::ENCODE_INVALID_TAG) === self::ENCODE_INVALID_TAG;
    }

    /**
     * @return bool
     */
    public function shallEncodeInvalidAttr()
    {
        return ($this->flags & self::ENCODE_INVALID_ATTR) === self::ENCODE_INVALID_ATTR;
    }

    /**
     * @return bool
     */
    public function shallRemoveUnexpectedChildren()
    {
        return ($this->flags & self::REMOVE_UNEXPECTED_CHILDREN) === self::REMOVE_UNEXPECTED_CHILDREN;
    }

    /**
     * @return bool
     */
    public function shallAllowCustomElements()
    {
        return ($this->flags & self::ALLOW_CUSTOM_ELEMENTS) === self::ALLOW_CUSTOM_ELEMENTS;
    }

    /**
     * @param string[] $names
     * @throws LogicException
     * @return void
     */
    protected function assertScalarUniqueness(array $names)
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
     * @return void
     */
    protected function assertTagUniqueness(array $tags)
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

    /**
     * @return string
     */
    protected function getTagName(Tag $tag)
    {
        return strtolower($tag->getName());
    }
}
