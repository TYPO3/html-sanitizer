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
    protected const mastermindsDefaultOptions = [
        // Whether the serializer should aggressively encode all characters as entities.
        'encode_entities' => false,
        // Prevents the parser from automatically assigning the HTML5 namespace to the DOM document.
        // (adjusted due to https://github.com/Masterminds/html5-php/issues/181#issuecomment-643767471)
        'disable_html_ns' => true,
    ];

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
        $this->handle($this->root, $initiator);
        return $this->serialize($this->root);
    }

    protected function parse(string $html): DOMDocumentFragment
    {
        return $this->parser->parseFragment($html);
    }

    protected function handle(DOMNode $domNode, InitiatorInterface $initiator = null): DOMNode
    {
        $this->context = new Context($this->parser, $initiator);
        $this->beforeTraverse();
        $this->traverseNodeList($domNode->childNodes);
        $this->afterTraverse();
        return $domNode;
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

    protected function traverse(DOMNode $domNode): void
    {
        foreach ($this->visitors as $visitor) {
            $result = $visitor->enterNode($domNode);
            $domNode = $this->replaceNode($domNode, $result);
            if ($domNode === null) {
                return;
            }
        }

        if ($domNode->hasChildNodes()) {
            $this->traverseNodeList($domNode->childNodes);
        }

        foreach ($this->visitors as $visitor) {
            $result = $visitor->leaveNode($domNode);
            $domNode = $this->replaceNode($domNode, $result);
            if ($domNode === null) {
                return;
            }
        }
    }

    /**
     * Traverses node-list (child-nodes) in reverse(!) order to allow
     * directly removing child nodes, keeping node-list indexes.
     *
     * @param DOMNodeList $domNodeList
     */
    protected function traverseNodeList(DOMNodeList $domNodeList): void
    {
        for ($i = $domNodeList->length - 1; $i >= 0; $i--) {
            /** @var DOMNode $item */
            $item = $domNodeList->item($i);
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
        return new HTML5(self::mastermindsDefaultOptions);
    }
}
