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

use DOMNode;
use RuntimeException;

class NodeException extends RuntimeException
{
    /**
     * @return $this
     */
    public static function create()
    {
        return new self('<node>', 1624911897);
    }

    /**
     * @var DOMNode|null
     */
    protected $node;

    /**
     * @param \DOMNode|null $node
     * @return $this
     */
    public function withNode($node)
    {
        $this->node = $node;
        return $this;
    }

    /**
     * @return \DOMNode|null
     */
    public function getNode()
    {
        return $this->node;
    }
}
