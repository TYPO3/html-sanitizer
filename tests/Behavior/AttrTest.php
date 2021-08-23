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

namespace TYPO3\HtmlSanitizer\Tests\Behavior;

use PHPUnit\Framework\TestCase;
use TYPO3\HtmlSanitizer\Behavior\Attr;
use TYPO3\HtmlSanitizer\Behavior\DatasetAttrValue;

class AttrTest extends TestCase
{
    /**
     * @test
     */
    public function addValuesKeepsInstance(): void
    {
        $valueA = new DatasetAttrValue('a1', 'a2');
        $valueB = new DatasetAttrValue('b1', 'b2');
        $valueC = new DatasetAttrValue('c1', 'c2');
        $attr = new Attr('test');
        self::assertSame($attr, $attr->addValues($valueA, $valueB, $valueC));
    }

    /**
     * @test
     */
    public function withValuesKeepsInstance(): void
    {
        $valueA = new DatasetAttrValue('a1', 'a2');
        $valueB = new DatasetAttrValue('b1', 'b2');
        $valueC = new DatasetAttrValue('c1', 'c2');
        $attr = new Attr('test');
        $attr = $attr->addValues($valueA, $valueB, $valueC);
        self::assertSame($attr, $attr->withValues($valueA));
        self::assertSame($attr, $attr->withValues($valueA, $valueB));
        self::assertSame($attr, $attr->withValues($valueA, $valueB, $valueC));
    }

    /**
     * @test
     */
    public function withValuesClonesInstance(): void
    {
        $valueA = new DatasetAttrValue('a1', 'a2');
        $valueB = new DatasetAttrValue('b1', 'b2');
        $valueC = new DatasetAttrValue('c1', 'c2');
        $attr = new Attr('test');
        $attr = $attr->addValues($valueA, $valueB, $valueC);

        $valueD = new DatasetAttrValue('d1', 'd2');
        $valueE = new DatasetAttrValue('e1', 'e2');
        self::assertNotSame($attr, $attr->withValues($valueD));
        self::assertNotSame($attr, $attr->withValues($valueD, $valueE));
        self::assertNotSame($attr, $attr->withValues($valueA, $valueD));
    }
}
