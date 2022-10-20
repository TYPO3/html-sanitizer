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
use TYPO3\HtmlSanitizer\Behavior\NodeInterface;
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
     * remove children at nodes that did not expect children
     */
    public const REMOVE_UNEXPECTED_CHILDREN = 4;

    /**
     * https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     * custom elements must contain a hyphen (`-`), start with ASCII lower alpha
     */
    public const ALLOW_CUSTOM_ELEMENTS = 8;

    /**
     * in case an unexpected comment was found, encode the whole comment as HTML
     */
    public const ENCODE_INVALID_COMMENT = 16;

    /**
     * in case an unexpected CDATA section was found, encode the whole CDATA section as HTML
     */
    public const ENCODE_INVALID_CDATA_SECTION = 32;

    /**
     * @var int
     */
    protected $flags = 0;

    /**
     * @var string
     */
    protected $name = 'undefined';

    /**
     * Node names as array index, e.g. `['strong' => new Tag('strong', '#comment' => new Comment()]`
     * @var array<string, ?NodeInterface>
     */
    protected $nodes = [
        // v2.1.0: adding `#comment` and `#cdata-section` hints for backward compatibility, will be removed with v3.0.0
        '#comment' => null,
        '#cdata-section' => null,
    ];

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

    /**
     * @todo deprecate
     */
    public function withTags(Tag ...$tags): self
    {
        return $this->withNodes(...$tags);
    }

    /**
     * @todo deprecate
     */
    public function withoutTags(Tag ...$tags): self
    {
        return $this->withoutNodes(...$tags);
    }

    public function withNodes(NodeInterface ...$nodes): self
    {
        $names = array_map([$this, 'getNodeName'], $nodes);
        $this->assertScalarUniqueness($names);
        // uses node name as array index, e.g. `['#comment' => new Comment()]`
        $indexedNodes = array_combine($names, $nodes);
        if (!is_array($indexedNodes)) {
            return $this;
        }
        $this->assertNodeUniqueness($indexedNodes);
        $target = clone $this;
        $target->nodes = array_merge($target->nodes, $indexedNodes);
        return $target;
    }

    public function withoutNodes(NodeInterface ...$nodes): self
    {
        $names = array_map([$this, 'getNodeName'], $nodes);
        $filteredNodes = array_filter(
            $this->nodes,
            static function (?NodeInterface $node, string $name) use ($nodes, $names) {
                return $node === null && !in_array($name, $names, true)
                    || $node !== null && !in_array($node, $nodes, true);
            },
            ARRAY_FILTER_USE_BOTH
        );
        if ($filteredNodes === $this->nodes) {
            return $this;
        }
        $target = clone $this;
        $target->nodes = $filteredNodes;
        return $target;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<Tag>
     */
    public function getTags(): array
    {
        return array_filter(
            $this->nodes,
            static function (NodeInterface $node) {
                return $node instanceof Tag;
            }
        );
    }

    public function getTag(string $name): ?Tag
    {
        $name = strtolower($name);
        $node = $this->nodes[$name] ?? null;
        return $node instanceof Tag ? $node : null;
    }

    /**
     * @return list<NodeInterface>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getNode(string $name): ?NodeInterface
    {
        $name = strtolower($name);
        return $this->nodes[$name] ?? null;
    }

    public function hasNode(string $name): bool
    {
        return array_key_exists($name, $this->nodes);
    }

    public function shallEncodeInvalidTag(): bool
    {
        return ($this->flags & self::ENCODE_INVALID_TAG) === self::ENCODE_INVALID_TAG;
    }

    public function shallEncodeInvalidAttr(): bool
    {
        return ($this->flags & self::ENCODE_INVALID_ATTR) === self::ENCODE_INVALID_ATTR;
    }

    public function shallEncodeInvalidComment(): bool
    {
        return ($this->flags & self::ENCODE_INVALID_COMMENT) === self::ENCODE_INVALID_COMMENT;
    }

    public function shallEncodeInvalidCdataSection(): bool
    {
        return ($this->flags & self::ENCODE_INVALID_CDATA_SECTION) === self::ENCODE_INVALID_CDATA_SECTION;
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
     * @param list<string> $names
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
     * @param array<string, NodeInterface> $nodes
     */
    protected function assertNodeUniqueness(array $nodes): void
    {
        $existingNodeNames = array_intersect_key(array_filter($this->nodes), $nodes);
        if ($existingNodeNames !== []) {
            throw new LogicException(
                sprintf(
                    'Cannot redeclare node names %s. Remove duplicates first',
                    implode(', ', array_keys($existingNodeNames))
                ),
                1625391217
            );
        }
    }

    protected function getNodeName(NodeInterface $node): string
    {
        return strtolower($node->getName());
    }
}
