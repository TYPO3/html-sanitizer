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

namespace TYPO3\HtmlSanitizer\Parser;

use DOMDocument;
use DOMDocumentFragment;
use Masterminds\HTML5 as MastermindsHTML5;
use Masterminds\HTML5\Parser\DOMTreeBuilder;
use Masterminds\HTML5\Parser\Scanner;

/**
 * Extends the Masterminds HTML5 parser to substitute the local Tokenizer
 * subclass, which fixes raw-text end-tag handling for whitespace variants.
 */
class Html5 extends MastermindsHTML5
{
    #[\Override]
    public function parse($input, array $options = []): DOMDocument
    {
        $this->errors = [];
        $options = array_merge($this->getOptions(), $options);
        $events = new DOMTreeBuilder(false, $options);
        $scanner = new Scanner($input, !empty($options['encoding']) ? $options['encoding'] : 'UTF-8');
        $parser = new Tokenizer(
            $scanner,
            $events,
            !empty($options['xmlNamespaces']) ? Tokenizer::CONFORMANT_XML : Tokenizer::CONFORMANT_HTML
        );

        $parser->parse();
        $this->errors = $events->getErrors();

        return $events->document();
    }

    #[\Override]
    public function parseFragment($input, array $options = []): DOMDocumentFragment
    {
        $options = array_merge($this->getOptions(), $options);
        $events = new DOMTreeBuilder(true, $options);
        $scanner = new Scanner($input, !empty($options['encoding']) ? $options['encoding'] : 'UTF-8');
        $parser = new Tokenizer(
            $scanner,
            $events,
            !empty($options['xmlNamespaces']) ? Tokenizer::CONFORMANT_XML : Tokenizer::CONFORMANT_HTML
        );

        $parser->parse();
        $this->errors = $events->getErrors();

        return $events->fragment();
    }
}
