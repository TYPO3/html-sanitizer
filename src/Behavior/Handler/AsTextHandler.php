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
use LogicException;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\HandlerInterface;
use TYPO3\HtmlSanitizer\Behavior\NodeInterface;
use TYPO3\HtmlSanitizer\Context;

class AsTextHandler implements HandlerInterface
{
    public function handle(NodeInterface $node, ?DOMNode $domNode, Context $context, Behavior $behavior = null): ?DOMNode
    {
        if ($domNode === null) {
            return null;
        }
        // @todo might use `DOMChildNode` with PHP 8
        if (($domNode->parentNode ?? null) === null) {
            throw new LogicException('Cannot process nodes not having a parent', 1666333132);
        }
        $text = new DOMText();
        $text->nodeValue = $context->parser->saveHTML($domNode);
        return $text;
    }
}
