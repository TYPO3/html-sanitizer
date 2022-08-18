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

namespace TYPO3\HtmlSanitizer\Tests;

use PHPUnit\Framework\TestCase;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

class ScenarioTest extends TestCase
{
    /**
     * @return array
     */
    public static function allTagsAreRemovedOnMissingDeclarationDataProvider()
    {
        return [
            ['<div class="content">value</div><span class="content">value</span>', ''],
            ['<!--any--><div class="content">value</div>', '<!--any-->'],
            ['<!--any--!><div class="content">value</div>', '<!--any-->'],
        ];
    }

    /**
     * @test
     * @dataProvider allTagsAreRemovedOnMissingDeclarationDataProvider
     * @return void
     */
    public function allTagsAreRemovedOnMissingDeclaration($payload, $expectation)
    {
        $behavior = new Behavior();
        $sanitizer = new Sanitizer([
            new CommonVisitor($behavior)
        ]);
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }
}
