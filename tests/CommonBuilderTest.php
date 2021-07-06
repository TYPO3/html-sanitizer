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

namespace TYPO3\HtmlSanitizer\Tests;

use PHPUnit\Framework\TestCase;
use TYPO3\HtmlSanitizer\Builder\CommonBuilder;

class CommonBuilderTest extends TestCase
{
    public function isSanitizedDataProvider(): array
    {
        return [
            '#010' => [
                '<unknown unknown="unknown">value</unknown>',
                '&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;',
            ],
            '#011' => [
                '<div class="nested"><unknown unknown="unknown">value</unknown></div>',
                '<div class="nested">&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;</div>',
            ],
            '#012' => [
                '&lt;script&gt;alert(1)&lt;/script&gt;',
                '&lt;script&gt;alert(1)&lt;/script&gt;',
            ],
            // @todo bug in https://github.com/Masterminds/html5-php/issues
            // '#013' => [
            //    '<strong>Given that x < y and y > z...</strong>',
            //    '<strong>Given that x &lt; y and y &gt; z...</strong>',
            // ],
            '#020' => [
                '<div unknown="unknown">value</div>',
                '<div>value</div>',
            ],
            '#030' => [
                '<div class="class">value</div>',
                '<div class="class">value</div>',
            ],
            '#031' => [
                '<div data-value="value">value</div>',
                '<div data-value="value">value</div>',
            ],
            '#032' => [
                '<div data-bool>value</div>',
                '<div data-bool>value</div>',
            ],
            '#040' => [
                '<img src="mailto:noreply@typo3.org" onerror="alert(1)">',
                '',
            ],
            '#041' => [
                '<img src="https://typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="https://typo3.org/logo.svg">',
            ],
            '#042' => [
                '<img src="http://typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="http://typo3.org/logo.svg">',
            ],
            '#043' => [
                '<img src="/typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="/typo3.org/logo.svg">',
            ],
            '#044' => [
                '<img src="typo3.org/logo.svg" onerror="alert(1)">',
                '<img src="typo3.org/logo.svg">',
            ],
            '#045' => [
                '<img src="//typo3.org/logo.svg" onerror="alert(1)">',
                '',
            ],
            '#050' => [
                '<a href="https://typo3.org/" role="button">value</a>',
                '<a href="https://typo3.org/" role="button">value</a>',
            ],
            '#051' => [
                '<a href="ssh://example.org/" role="button">value</a>',
                '<a role="button">value</a>',
            ],
            '#052' => [
                '<a href="javascript:alert(1)" role="button">value</a>',
                '<a role="button">value</a>',
            ],
            '#053' => [
                '<a href="data:text/html;..." role="button">value</a>',
                '<a role="button">value</a>',
            ],
            '#090' => [
                '<p data-bool><span data-bool><strong data-bool>value</strong></span></p>',
                '<p data-bool><span data-bool><strong data-bool>value</strong></span></p>'
            ],
            '#900' => [
                '<div id="main">' .
                    '<a href="https://typo3.org/" data-type="url" wrong-attr="is-removed">TYPO3</a><br>' .
                    '(the <script>alert(1)</script> tag shall be encoded to HTML entities)'.
                '</div>',
                '<div id="main">' .
                    '<a href="https://typo3.org/" data-type="url">TYPO3</a><br>' .
                    '(the &lt;script&gt;alert(1)&lt;/script&gt; tag shall be encoded to HTML entities)'.
                '</div>',
            ],
        ];
    }

    /**
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isSanitizedDataProvider
     */
    public function isSanitized(string $payload, string $expectation): void
    {
        $builder = new CommonBuilder();
        $sanitizer = $builder->build();
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }
}
