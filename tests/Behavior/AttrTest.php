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
use TYPO3\HtmlSanitizer\Behavior\AttrValueInterface;
use TYPO3\HtmlSanitizer\Behavior\DatasetAttrValue;

class AttrTest extends TestCase
{
    /**
     * @test
     */
    public function withFlagsClonesInstance()
    {
        $attr = new Attr('test', Attr::BLUNT);
        $modifiedAttr = $attr->withFlags(Attr::MANDATORY);
        self::assertNotSame($attr, $modifiedAttr);
        self::assertEquals(Attr::BLUNT, $attr->getFlags());
        self::assertEquals(Attr::MANDATORY, $modifiedAttr->getFlags());
    }

    /**
     * @test
     */
    public function addValuesKeepsInstance()
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
    public function withValuesKeepsInstanceWhenNotModified()
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
    public function withValuesClonesInstanceWhenModified()
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

    public static function matchesNameDataProvider(): array
    {
        return [
            [ Attr::BLUNT, 'name', 'name', true ],
            [ Attr::BLUNT, 'name', 'other', false ],
            [ Attr::BLUNT, 'name', 'name-other', false ],
            [ Attr::NAME_PREFIX, 'name-', 'name-', true ],
            [ Attr::NAME_PREFIX, 'name-', 'name-other', true ],
            [ Attr::NAME_PREFIX, 'name-', 'name', false ],
            [ Attr::NAME_PREFIX, 'name-', 'other', false ],
        ];
    }

    /**
     * @param int $flags
     * @param string $name
     * @param string $matchName
     * @param bool $expectation
     * @test
     * @dataProvider matchesNameDataProvider
     */
    public function matchesName(int $flags, string $name, string $matchName, bool $expectation)
    {
        $attr = new Attr($name, $flags);
        self::assertSame($expectation, $attr->matchesName($matchName));
    }

    public static function matchesValueDataProvider(): array
    {
        $equalsA = new DatasetAttrValue('a');
        $equalsB = new DatasetAttrValue('b');
        $equalsAorB = new DatasetAttrValue('a', 'b');

        return [
            [ Attr::MATCH_ALL_VALUES, [$equalsA], 'a', true ],
            [ Attr::MATCH_ALL_VALUES, [$equalsA], 'b', false ],
            [ Attr::MATCH_ALL_VALUES, [$equalsAorB], 'a', true ],
            [ Attr::MATCH_ALL_VALUES, [$equalsA, $equalsAorB], 'a', true ],
            [ Attr::MATCH_ALL_VALUES, [$equalsA, $equalsB], 'a', false ], // both `$equalsA` and `$equalsB` must match
            [ Attr::MATCH_ALL_VALUES, [$equalsA, $equalsB], 'b', false ], // both `$equalsA` and `$equalsB` must match
            [ Attr::MATCH_ALL_VALUES, [$equalsA, $equalsB], 'c', false ],
            [ Attr::MATCH_ALL_VALUES, [$equalsA, $equalsB, $equalsAorB], 'c', false ],
            [ Attr::BLUNT, [$equalsA], 'a', true ],
            [ Attr::BLUNT, [$equalsA], 'b', false ],
            [ Attr::BLUNT, [$equalsAorB], 'a', true ],
            [ Attr::BLUNT, [$equalsA, $equalsAorB], 'a', true ],
            [ Attr::BLUNT, [$equalsA, $equalsB], 'a', true ],
            [ Attr::BLUNT, [$equalsA, $equalsB], 'b', true ],
            [ Attr::BLUNT, [$equalsA, $equalsB], 'c', false ],
            [ Attr::BLUNT, [$equalsA, $equalsB, $equalsAorB], 'c', false ],
        ];
    }

    /**
     * @param int $flags
     * @param AttrValueInterface[] $values
     * @param string $matchValue
     * @param bool $expectation
     * @test
     * @dataProvider matchesValueDataProvider
     */
    public function matchesValue(int $flags, array $values, string $matchValue, bool $expectation)
    {
        $attr = new Attr('test', $flags);
        $attr->addValues(...$values);
        self::assertSame($expectation, $attr->matchesValue($matchValue));
    }
}
