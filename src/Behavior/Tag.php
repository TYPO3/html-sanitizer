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

namespace TYPO3\HtmlSanitizer\Behavior;

use LogicException;

/**
 * Model of tag
 */
class Tag
{
    /**
     * not having any behavioral capabilities
     */
    const BLUNT = 0;

    /**
     * whether to purge this tag in case it does not have any attributes
     */
    const PURGE_WITHOUT_ATTRS = 1;

    /**
     * whether to purge this tag in case it does not have children
     */
    const PURGE_WITHOUT_CHILDREN = 2;

    /**
     * whether this tag allows to have children
     */
    const ALLOW_CHILDREN = 8;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $flags = 0;

    /**
     * @var array<string, Attr>
     */
    protected $attrs = [];

    /**
     * @param string $name
     * @param int $flags
     */
    public function __construct($name, $flags = null)
    {
        $name = (string) $name;
        $flags = (int) $flags;
        $this->name = $name;
        // using `null` as default - potentially allows switching
        // the real default value from `BLUNT` to e.g. `ALLOW_CHILDREN`
        $this->flags = isset($flags) ? $flags : self::BLUNT;

        if ($this->shallPurgeWithoutChildren() && !$this->shallAllowChildren()) {
            throw new LogicException(
                sprintf('Tag %s does not allow children, but shall be purged without them', $name),
                1625397681
            );
        }
    }

    /**
     * @return $this
     */
    public function addAttrs(Attr ...$attrs)
    {
        $names = array_map([$this, 'getAttrName'], $attrs);
        $this->assertScalarUniqueness($names);
        $indexedAttrs = array_combine($names, $attrs);
        if (!is_array($indexedAttrs)) {
            return $this;
        }
        $this->assertAttrUniqueness($indexedAttrs);
        $this->attrs = array_merge($this->attrs, $indexedAttrs);
        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getAttrs()
    {
        return $this->attrs;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function shallPurgeWithoutAttrs()
    {
        return ($this->flags & self::PURGE_WITHOUT_ATTRS) === self::PURGE_WITHOUT_ATTRS;
    }

    /**
     * @return bool
     */
    public function shallPurgeWithoutChildren()
    {
        return ($this->flags & self::PURGE_WITHOUT_CHILDREN) === self::PURGE_WITHOUT_CHILDREN;
    }

    /**
     * @return bool
     */
    public function shallAllowChildren()
    {
        return ($this->flags & self::ALLOW_CHILDREN) === self::ALLOW_CHILDREN;
    }

    /**
     * @return \TYPO3\HtmlSanitizer\Behavior\Attr|null
     * @param string $name
     */
    public function getAttr($name)
    {
        $name = (string) $name;
        $name = strtolower($name);
        if (isset($this->attrs[$name])) {
            return $this->attrs[$name];
        }
        foreach ($this->attrs as $attr) {
            if ($attr->matchesName($name)) {
                return $attr;
            }
        }
        return null;
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
                    'Ambiguous attr names %s.',
                    implode(', ', $ambiguousNames)
                ),
                1625590355
            );
        }
    }

    /**
     * @param array<string, Attr> $attrs
     * @throws LogicException
     * @return void
     */
    protected function assertAttrUniqueness(array $attrs)
    {
        $existingAttrNames = [];
        $currentAttrNames = array_keys($this->attrs);
        foreach ($attrs as $attr) {
            $currentAttr = $this->getAttr($attr->getName());
            // finds exact matches, and new static attrs that already have existing prefixed attrs
            if ($currentAttr !== null) {
                $existingAttrNames[] = $attr->getName();
            // finds new prefixed attrs that would be ambiguous for existing attrs
            } elseif ($attr->isPrefix()) {
                foreach ($currentAttrNames as $currentAttrName) {
                    if ($attr->matchesName($currentAttrName)) {
                        $existingAttrNames[] = $attr->getName();
                        break;
                    }
                }
            }
        }
        $existingAttrNames = array_filter($existingAttrNames);
        if ($existingAttrNames !== []) {
            throw new LogicException(
                sprintf(
                    'Cannot redeclare attr names %s.',
                    implode(', ', $existingAttrNames)
                ),
                1625394715
            );
        }
    }

    /**
     * @return string
     */
    protected function getAttrName(Attr $attr)
    {
        return strtolower($attr->getName());
    }
}
