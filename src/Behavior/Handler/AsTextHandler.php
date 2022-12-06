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

namespace TYPO3\HtmlSanitizer\Behavior\Handler;

use DOMNode;
use DOMText;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\HandlerInterface;
use TYPO3\HtmlSanitizer\Behavior\NodeInterface;
use TYPO3\HtmlSanitizer\Context;

class AsTextHandler implements HandlerInterface
{
    /**
     * @param NodeInterface $node
     * @param DOMNode|null $domNode
     * @param Context $context
     * @param Behavior|null $behavior
     * @return DOMNode|null
     */
    public function handle(NodeInterface $node, $domNode, Context $context, Behavior $behavior = null)
    {
        if ($domNode === null) {
            return null;
        }
        return new DOMText($context->parser->saveHTML($domNode));
    }
}
