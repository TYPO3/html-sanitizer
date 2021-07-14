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

use LogicException;
use PHPUnit\Framework\TestCase;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\Tag;

class BehaviorTest extends TestCase
{
    /**
     * @return mixed[]
     */
    public function ambiguityIsDetectedDataProvider()
    {
        return [
            [ ['same'], ['same'], 1625391217 ],
            [ ['same', 'same'], [], 1625591503 ],
            [ ['same', 'same'], ['same'], 1625591503 ],
            [ [], ['same', 'same'], 1625591503 ],
            [ ['same'], ['same', 'same'], 1625591503 ],
        ];
    }

    /**
     * @param string[] $originalNames
     * @param string[] $additionalNames
     * @param int $code
     * @test
     * @dataProvider ambiguityIsDetectedDataProvider
     * @return void
     */
    public function ambiguityIsDetected(array $originalNames, array $additionalNames, $code = 0)
    {
        $code = (int) $code;
        $this->expectException(LogicException::class);
        $this->expectExceptionCode($code);
        $behavior = new Behavior();
        if (!empty($originalNames)) {
            $behavior = $behavior->withTags(...$this->createTags(...$originalNames));
        }
        if (!empty($additionalNames)) {
            $behavior = $behavior->withTags(...$this->createTags(...$additionalNames));
        }
    }

    /**
     * @param string ...$names
     * @return mixed[]
     */
    private function createTags(...$names)
    {
        return array_map(
            function ($name) {
                $name = (string) $name;
                return new Tag($name);
            },
            $names
        );
    }
}
