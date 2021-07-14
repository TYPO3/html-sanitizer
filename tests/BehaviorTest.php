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
        try {
            $behavior = new Behavior();
            if (!empty($originalNames)) {
                $createdTags = call_user_func_array([$this, 'createTags'], $originalNames);
                $behavior = call_user_func_array([$behavior, 'withTags'], $createdTags);
            }
            if (!empty($additionalNames)) {
                $createdAdditionalTags = call_user_func_array([$this, 'createTags'], $additionalNames);
                $behavior = call_user_func_array([$behavior, 'withTags'], $createdAdditionalTags);
            }
        } catch (\Exception $e) {
            $this->assertInstanceOf(LogicException::class, $e);
            $this->assertSame($code, $e->getCode());
            return;
        }

        $this->fail(sprintf('Expected to throw %s with code %d', LogicException::class, $code));
    }

    /**
     * @return mixed[]
     */
    private function createTags()
    {
        return array_map(
            function ($name) {
                $name = (string) $name;
                return new Tag($name);
            },
            func_get_args()
        );
    }
}
