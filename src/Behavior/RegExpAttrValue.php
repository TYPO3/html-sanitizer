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

class RegExpAttrValue implements AttrValueInterface
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @param string $pattern
     */
    public function __construct($pattern)
    {
        $pattern = (string) $pattern;
        $this->pattern = $pattern;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function matches($value)
    {
        $value = (string) $value;
        $matches = preg_match($this->pattern, $value) > 0;
        $regExpError = preg_last_error();
        if ($regExpError === PREG_NO_ERROR) {
            return $matches;
        }
        throw new LogicException(
            sprintf('RegExp error %d', $regExpError),
            1624915659
        );
    }
}
