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

namespace TYPO3\HtmlSanitizer;

use DOMDocumentFragment;
use DOMNode;
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

    /**
     * @param VisitorInterface[] $visitors
     */
    public function __construct(array $visitors = [])
    {
        $this->visitors = $visitors;
        $this->parser = $this->createParser();
    }

    /**
     * @param string $html
     * @return string
     */
    public function sanitize($html)
    {
        $html = (string) $html;
        $this->root = $this->parse($html);
        $this->context = new Context($this->parser);
        $this->beforeTraverse();
        foreach ($this->root->childNodes as $childNode) {
            $this->traverse($childNode);
        }
        $this->afterTraverse();
        return $this->serialize($this->root);
    }

    /**
     * @param string $html
     * @return \DOMDocumentFragment
     */
    protected function parse($html)
    {
        $html = (string) $html;
        return $this->parser->parseFragment($html);
    }

    /**
     * @return string
     */
    protected function serialize(DOMNode $document)
    {
        return $this->parser->saveHTML($document);
    }

    /**
     * @return void
     */
    protected function beforeTraverse()
    {
        foreach ($this->visitors as $visitor) {
            $visitor->beforeTraverse($this->context);
        }
    }

    /**
     * @return void
     */
    protected function traverse(DOMNode $node)
    {
        foreach ($this->visitors as $visitor) {
            $result = $visitor->enterNode($node);
            $node = $this->replaceNode($node, $result);
            if ($node === null) {
                return;
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                $this->traverse($childNode);
            }
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
     * @return void
     */
    protected function afterTraverse()
    {
        foreach ($this->visitors as $visitor) {
            $visitor->afterTraverse($this->context);
        }
    }

    /**
     * @param \DOMNode|null $target
     * @return \DOMNode|null
     */
    protected function replaceNode(DOMNode $source, DOMNode $target = null)
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

    /**
     * @return \Masterminds\HTML5
     */
    protected function createParser()
    {
        // set parser & applies work-around
        // https://github.com/Masterminds/html5-php/issues/181#issuecomment-643767471
        return new HTML5([
            'disable_html_ns' => true,
        ]);
    }
}
