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

use Masterminds\HTML5\Elements;
use Masterminds\HTML5\Parser\Tokenizer as MastermindsTokenizer;

/**
 * Extends the Masterminds tokenizer to fix rawText() so that it recognises
 * whitespace-variant closing tags (e.g. </style\t>) as valid end tags per
 * HTML5 spec § 8.2.6.1, aligning it with the existing rcdata() behaviour.
 */
class Tokenizer extends MastermindsTokenizer
{
    #[\Override]
    protected function rawText($tok): bool
    {
        if ($this->untilTag === null) {
            return $this->text($tok);
        }

        // Search for `'</' . $untilTag` without the trailing '>' so that
        // optional whitespace before '>' is handled correctly, matching
        // the behaviour of rcdata() and the HTML5 spec (§ 8.2.6.1).
        // Entity references are NOT decoded in raw text (unlike rcdata).
        $sequence = '</' . $this->untilTag;
        $txt = '';

        $caseSensitive = !Elements::isHtml5Element($this->untilTag);
        while ($tok !== false &&
            ($tok !== '<' || !$this->scanner->sequenceMatches($sequence, $caseSensitive))
        ) {
            $txt .= $tok;
            $tok = $this->scanner->next();
        }

        if ($tok === false) {
            $this->parseError('Unexpected EOF during raw text read.');
            $this->events->text($txt);
            $this->setTextMode(0);
            return false;
        }

        $len = strlen($sequence);
        $this->scanner->consume($len);
        $len += $this->scanner->whitespace();
        if ($this->scanner->current() !== '>') {
            $this->parseError('Unclosed raw text end tag');
        }

        $this->scanner->unconsume($len);
        $this->events->text($txt);
        $this->setTextMode(0);

        return $this->endTag();
    }
}
