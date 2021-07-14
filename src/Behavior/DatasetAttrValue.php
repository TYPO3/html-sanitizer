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

class DatasetAttrValue implements AttrValueInterface
{
    /**
     * @var string[]
     */
    protected $dataset;

    /**
     * @param string ...$dataset
     */
    public function __construct(...$dataset)
    {
        $this->dataset = $dataset;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function matches($value)
    {
        $value = (string) $value;
        return in_array($value, $this->dataset, true);
    }
}
