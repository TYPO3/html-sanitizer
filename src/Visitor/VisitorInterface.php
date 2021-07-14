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

namespace TYPO3\HtmlSanitizer\Visitor;

use DOMNode;
use TYPO3\HtmlSanitizer\Context;

/**
 * Interface for custom node visitors.
 */
interface VisitorInterface
{
    public function beforeTraverse(Context $context);

    /**
     * @return \DOMNode|null
     */
    public function enterNode(DOMNode $node);

    /**
     * @return \DOMNode|null
     */
    public function leaveNode(DOMNode $node);

    public function afterTraverse(Context $context);
}
