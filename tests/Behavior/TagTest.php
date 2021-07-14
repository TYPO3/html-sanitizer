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

namespace TYPO3\HtmlSanitizer\Tests\Behavior;

use LogicException;
use PHPUnit\Framework\TestCase;
use TYPO3\HtmlSanitizer\Behavior\Attr;
use TYPO3\HtmlSanitizer\Behavior\Tag;

class TagTest extends TestCase
{
    /**
     * @return mixed[]
     */
    public function ambiguityIsDetectedDataProvider()
    {
        return [
            [ ['same'], ['same'], 1625394715 ],
            [ ['same', 'same'], [], 1625590355 ],
            [ ['same', 'same'], ['same'], 1625590355 ],
            [ [], ['same', 'same'], 1625590355 ],
            [ ['same'], ['same', 'same'], 1625590355 ],
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
        $tag = new Tag('tag');
        if (!empty($originalNames)) {
            $tag->addAttrs(...$this->createAttrs(...$originalNames));
        }
        if (!empty($additionalNames)) {
            $tag->addAttrs(...$this->createAttrs(...$additionalNames));
        }
    }

    /**
     * @param string ...$names
     * @return mixed[]
     */
    private function createAttrs(...$names)
    {
        return array_map(
            function ($name) {
                $name = (string) $name;
                return new Attr($name);
            },
            $names
        );
    }
}
