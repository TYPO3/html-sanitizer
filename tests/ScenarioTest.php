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
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

class ScenarioTest extends TestCase
{
    public static function tagFlagsAreProcessedDataProvider(): array
    {
        return [
            [
                Behavior\Tag::ALLOW_CHILDREN + Behavior\Tag::PURGE_WITHOUT_CHILDREN,
                implode("\n", [
                    '<div></div><div data-test="test"></div>',
                    '<div>test</div><div data-test="test">test</div>',
                    '<div><!-- --></div><div data-test="test"><!-- --></div>',
                    '<div><!-- test --></div><div data-test="test"><!-- test --></div>',
                    '<div><i></i></div><div data-test="test"><i></i></div>',
                ]),
                implode("\n", [
                    '',
                    '<div>test</div><div data-test="test">test</div>',
                    '<div><!-- --></div><div data-test="test"><!-- --></div>',
                    '<div><!-- test --></div><div data-test="test"><!-- test --></div>',
                    '<div><i></i></div><div data-test="test"><i></i></div>',
                ]),
            ],
            [
                Behavior\Tag::ALLOW_CHILDREN + Behavior\Tag::PURGE_WITHOUT_CHILDREN,
                implode("\n", [
                    '<script></script><script data-test="test"></script>',
                    '<script>test</script><script data-test="test">test</script>',
                    '<script><!-- --></script><script data-test="test"><!-- --></script>',
                    '<script><!-- test --></script><script data-test="test"><!-- test --></script>',
                ]),
                implode("\n", [
                    '',
                    '<script>test</script><script data-test="test">test</script>',
                    '<script><!-- --></script><script data-test="test"><!-- --></script>',
                    '<script><!-- test --></script><script data-test="test"><!-- test --></script>',
                ]),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider tagFlagsAreProcessedDataProvider
     */
    public function tagFlagsAreProcessed(int $flags, string $payload, string $expectation): void
    {
        $behavior = (new Behavior())
            ->withFlags(Behavior::ENCODE_INVALID_TAG + Behavior::REMOVE_UNEXPECTED_CHILDREN)
            ->withName('scenario-test')
            ->withTags(
                (new Behavior\Tag('i')), // just used as DOM child element
                (new Behavior\Tag('div', $flags))->addAttrs((new Behavior\Attr('data-test'))),
                (new Behavior\Tag('script', $flags))->addAttrs((new Behavior\Attr('data-test')))
            );

        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }
}
