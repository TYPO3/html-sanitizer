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

/**
 * Model of tag attribute
 */
class Attr
{
    /**
     * whether given name shall be considered as prefix, e.g.
     * `data-` or `aria-` for multiple similar and safe attribute names
     */
    const NAME_PREFIX = 1;

    /**
     * whether the first match in `$values` shall be considered
     * as indicator the attribute value is valid in general - if
     * this flag is not given, all declared `$values` must match
     */
    const MATCH_FIRST_VALUE = 2;

    /**
     * either specific attribute name (`class`) or a prefix
     * (`data-`) in case corresponding NAME_PREFIX flag is set
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $flags = 0;

    /**
     * @var AttrValueInterface[]
     */
    protected $values = [];

    /**
     * @param string $name
     * @param int $flags
     */
    public function __construct($name, $flags = 0)
    {
        $name = (string) $name;
        $flags = (int) $flags;
        $this->name = $name;
        $this->flags = $flags;
    }

    /**
     * @return $this
     */
    public function addValues()
    {
        $this->values = array_merge($this->values, func_get_args());
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return AttrValueInterface[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return bool
     */
    public function isPrefix()
    {
        return ($this->flags & self::NAME_PREFIX) === self::NAME_PREFIX;
    }

    /**
     * @return bool
     */
    public function shallMatchFirstValue()
    {
        return ($this->flags & self::MATCH_FIRST_VALUE) === self::MATCH_FIRST_VALUE;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function matchesName($name)
    {
        $name = (string) $name;
        $name = strtolower($name);
        return $name === $this->name
            || $this->isPrefix() && strpos($name, $this->name) === 0;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function matchesValue($value)
    {
        $value = (string) $value;
        // no declared assertions means `true` as well
        if ($this->values === []) {
            return true;
        }
        $matchFirstValue = $this->shallMatchFirstValue();
        foreach ($this->values as $assertion) {
            // + result: false, matchFirstValue: false --> return false
            // + result: true, matchFirstValue: true --> return true
            // (anything else continues processing)
            $result = $assertion->matches($value);
            if ($result === $matchFirstValue) {
                return $matchFirstValue;
            }
        }
        // + matchFirstValue: false --> return true (since no other match failed before)
        // + matchFirstValue: true --> return false (since no other match succeeded before)
        return !$matchFirstValue;
    }
}
