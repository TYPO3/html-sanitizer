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

use DOMDocumentFragment;
use DOMNode;
use DOMNodeList;
use Masterminds\HTML5;
use TYPO3\HtmlSanitizer\Visitor\VisitorInterface;

/**
 * HTML Sanitizer in a nutshell:
 *
 * + `Behavior` contains declarative settings for a particular process for sanitizing HTML.
 * + `Visitor` (multiple different visitors can exist at the same time) are actually doing the
 *   work based on the declared `Behavior`. Visitors can modify nodes or mark them for deletion.
 * + `Sanitizer` can be considered as the working instance, invoking visitors, parsing and
 *   serializing HTML. In general this instance does not contain much logic on how to handle
 *   particular nodes, attributes or values
 *
 * This `Sanitizer` class is agnostic specific configuration - it's purpose is to parse HTML,
 * invoke all registered visitors (they actually do the work and contain specific logic) and
 * finally provide HTML serialization as string again.
 */
class Sanitizer
{
    /**
     * @var VisitorInterface[]
     */
    protected $visitors = [];

    /**
     * @var HTML5
     */
    protected $parser;

    /**
     * @var DOMDocumentFragment
     */
    protected $root;

    /**
     * @var Context
     */
    protected $context;

    public function __construct(VisitorInterface ...$visitors)
    {
        $this->visitors = $visitors;
        $this->parser = $this->createParser();
    }

    public function sanitize(string $html, InitiatorInterface $initiator = null): string
    {
        $this->root = $this->parse($html);
        $this->context = new Context($this->parser, $initiator);
        $this->beforeTraverse();
        $this->traverseNodeList($this->root->childNodes);
        $this->afterTraverse();
        return $this->serialize($this->root);
    }

    protected function parse(string $html): DOMDocumentFragment
    {
        return $this->parser->parseFragment($html);
    }

    protected function serialize(DOMNode $document): string
    {
        return $this->parser->saveHTML($document);
    }

    protected function beforeTraverse(): void
    {
        foreach ($this->visitors as $visitor) {
            $visitor->beforeTraverse($this->context);
        }
    }

    protected function traverse(DOMNode $node): void
    {
        foreach ($this->visitors as $visitor) {
            $result = $visitor->enterNode($node);
            $node = $this->replaceNode($node, $result);
            if ($node === null) {
                return;
            }
        }

        if ($node->hasChildNodes()) {
            $this->traverseNodeList($node->childNodes);
        }

        foreach ($this->visitors as $visitor) {
            $result = $visitor->leaveNode($node);
            $node = $this->replaceNode($node, $result);
            if ($node === null) {
                return;
            }
        }
    }

    /**
     * Traverses node-list (child-nodes) in reverse(!) order to allow
     * directly removing child nodes, keeping node-list indexes.
     *
     * @param DOMNodeList $nodeList
     */
    protected function traverseNodeList(DOMNodeList $nodeList): void
    {
        for ($i = $nodeList->length - 1; $i >= 0; $i--) {
            /** @var DOMNode $item */
            $item = $nodeList->item($i);
            $this->traverse($item);
        }
    }

    protected function afterTraverse(): void
    {
        foreach ($this->visitors as $visitor) {
            $visitor->afterTraverse($this->context);
        }
    }

    protected function replaceNode(DOMNode $source, ?DOMNode $target): ?DOMNode
    {
        if ($target === null) {
            $source->parentNode->removeChild($source);
        } elseif ($source !== $target) {
            if ($source->ownerDocument !== $target->ownerDocument) {
                $source->ownerDocument->importNode($target);
            }
            $source->parentNode->replaceChild($target, $source);
        }
        return $target;
    }

    protected function createParser(): HTML5
    {
        // set parser & applies work-around
        // https://github.com/Masterminds/html5-php/issues/181#issuecomment-643767471
        return new HTML5([
            'disable_html_ns' => true,
        ]);
    }
}
