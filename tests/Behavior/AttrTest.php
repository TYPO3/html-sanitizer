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
    public function matchesName(int $flags, string $name, string $matchName, bool $expectation): void
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
            [ Attr::BLUNT, [$equalsA], 'a', true ],
            [ Attr::BLUNT, [$equalsAorB], 'a', true ],
            [ Attr::BLUNT, [$equalsA, $equalsAorB], 'a', true ],
            [ Attr::BLUNT, [$equalsA, $equalsB], 'a', false ], // both `$equalsA` and `$equalsB` must match
            [ Attr::BLUNT, [$equalsA, $equalsB], 'b', false ], // both `$equalsA` and `$equalsB` must match
            [ Attr::BLUNT, [$equalsA, $equalsB], 'c', false ],
            [ Attr::BLUNT, [$equalsA, $equalsB, $equalsAorB], 'c', false ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsA], 'a', true ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsAorB], 'a', true ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsA, $equalsAorB], 'a', true ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsA, $equalsB], 'a', true ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsA, $equalsB], 'b', true ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsA, $equalsB], 'c', false ],
            [ Attr::MATCH_FIRST_VALUE, [$equalsA, $equalsB, $equalsAorB], 'c', false ],
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
    public function matchesValue(int $flags, array $values, string $matchValue, bool $expectation): void
    {
        $attr = new Attr('test', $flags);
        $attr->addValues(...$values);
        self::assertSame($expectation, $attr->matchesValue($matchValue));
    }
}
