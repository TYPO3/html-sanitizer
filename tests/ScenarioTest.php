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

use DOMNode;
use DOMText;
use PHPUnit\Framework\TestCase;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\Attr\UriAttrValueBuilder;
use TYPO3\HtmlSanitizer\Behavior\NodeInterface;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

class ScenarioTest extends TestCase
{
    public static function allTagsAreRemovedOnMissingDeclarationDataProvider(): array
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
     */
    public function allTagsAreRemovedOnMissingDeclaration(string $payload, string $expectation): void
    {
        $behavior = new Behavior();
        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    public static function tagFlagsAreProcessedDataProvider(): array
    {
        return [
            [
                Behavior\Tag::ALLOW_CHILDREN | Behavior\Tag::PURGE_WITHOUT_CHILDREN,
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
                Behavior\Tag::ALLOW_CHILDREN | Behavior\Tag::PURGE_WITHOUT_CHILDREN,
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
            ->withFlags(Behavior::ENCODE_INVALID_TAG | Behavior::REMOVE_UNEXPECTED_CHILDREN)
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

    public static function tagIsHandledDataProcessor(): array
    {
        $node = new Behavior\Tag('div');
        $asTextHandler = new Behavior\Handler\AsTextHandler();
        $closureHandler = new Behavior\Handler\ClosureHandler(
            static function (NodeInterface $node, ?DOMNode $domNode): ?\DOMNode {
                if ($domNode === null) {
                    return null;
                }
                return new DOMText(sprintf('Handled <%s>', $domNode->nodeName));
            }
        );

        return [
            [
                new Behavior\NodeHandler(
                    $node,
                    $asTextHandler
                ),
                '<div invalid-attr="value"><i>unexpected</i></div>',
                '&lt;div invalid-attr="value"&gt;&lt;i&gt;unexpected&lt;/i&gt;&lt;/div&gt;',
            ],
            [
                new Behavior\NodeHandler(
                    $node,
                    $asTextHandler,
                    Behavior\NodeHandler::PROCESS_DEFAULTS
                ),
                '<div invalid-attr="value"><i>unexpected</i></div>',
                '&lt;div&gt;&lt;/div&gt;',
            ],
            [
                new Behavior\NodeHandler(
                    $node,
                    $asTextHandler,
                    Behavior\NodeHandler::PROCESS_DEFAULTS | Behavior\NodeHandler::HANDLE_FIRST
                ),
                '<div invalid-attr="value"><i>unexpected</i></div>',
                '&lt;div invalid-attr="value"&gt;&lt;i&gt;unexpected&lt;/i&gt;&lt;/div&gt;',
            ],
            [
                new Behavior\NodeHandler(
                    $node,
                    $closureHandler
                ),
                '<div invalid-attr="value"><i>unexpected</i></div>',
                'Handled &lt;div&gt;',
            ],
            [
                new Behavior\NodeHandler(
                    $node,
                    $closureHandler,
                    Behavior\NodeHandler::PROCESS_DEFAULTS
                ),
                '<div invalid-attr="value"><i>unexpected</i></div>',
                'Handled &lt;div&gt;',
            ],
            [
                new Behavior\NodeHandler(
                    $node,
                    $closureHandler,
                    Behavior\NodeHandler::PROCESS_DEFAULTS | Behavior\NodeHandler::HANDLE_FIRST
                ),
                '<div invalid-attr="value"><i>unexpected</i></div>',
                'Handled &lt;div&gt;',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider tagIsHandledDataProcessor
     */
    public function tagIsHandled(Behavior\NodeHandler $nodeHandler, string $payload, string $expectation): void
    {
        $behavior = (new Behavior())
            ->withFlags(Behavior::REMOVE_UNEXPECTED_CHILDREN)
            ->withName('scenario-test')
            ->withNodes($nodeHandler);
        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    public static function commentsAreHandledDataProvider(): array
    {
        return [
            'not allowed' => [
                false,
                Behavior::BLUNT,
                '<div><!-- before -->test<!-- after --></div>',
                '<div>test</div>'
            ],
            'allowed' => [
                true,
                Behavior::BLUNT,
                '<div><!-- before -->test<!-- after --></div>',
                '<div><!-- before -->test<!-- after --></div>'
            ],
            'not allowed, encoded' => [
                false,
                Behavior::ENCODE_INVALID_COMMENT,
                '<div><!-- before -->test<!-- after --></div>',
                '<div>&lt;!-- before --&gt;test&lt;!-- after --&gt;</div>',
            ],
            'allowed, encoded' => [
                true,
                Behavior::ENCODE_INVALID_COMMENT,
                '<div><!-- before -->test<!-- after --></div>',
                '<div><!-- before -->test<!-- after --></div>'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider commentsAreHandledDataProvider
     */
    public function commentsAreHandled(bool $allowed, int $flags, string $payload, string $expectation): void
    {
        $behavior = (new Behavior())
            ->withFlags($flags)
            ->withName('scenario-test')
            ->withTags(new Behavior\Tag('div', Behavior\Tag::ALLOW_CHILDREN));
        $comment = new Behavior\Comment();
        $behavior = $allowed ? $behavior->withNodes($comment) : $behavior->withoutNodes($comment);
        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    public static function cdataSectionsAreHandledDataProvider(): array
    {
        return [
            'not allowed' => [
                false,
                Behavior::BLUNT,
                '<div><![CDATA[ before ]]>test<![CDATA[ after ]]></div>',
                '<div>test</div>'
            ],
            'allowed' => [
                true,
                Behavior::BLUNT,
                '<div><![CDATA[ before ]]>test<![CDATA[ after ]]></div>',
                '<div><![CDATA[ before ]]>test<![CDATA[ after ]]></div>'
            ],
            'not allowed, encoded' => [
                false,
                Behavior::ENCODE_INVALID_CDATA_SECTION,
                '<div><![CDATA[ before ]]>test<![CDATA[ after ]]></div>',
                '<div>&lt;![CDATA[ before ]]&gt;test&lt;![CDATA[ after ]]&gt;</div>',
            ],
            'allowed, encoded' => [
                true,
                Behavior::ENCODE_INVALID_CDATA_SECTION,
                '<div><![CDATA[ before ]]>test<![CDATA[ after ]]></div>',
                '<div><![CDATA[ before ]]>test<![CDATA[ after ]]></div>'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cdataSectionsAreHandledDataProvider
     */
    public function cdataSectionsAreHandled(bool $allowed, int $flags, string $payload, string $expectation): void
    {
        $behavior = (new Behavior())
            ->withFlags($flags)
            ->withName('scenario-test')
            ->withTags(new Behavior\Tag('div', Behavior\Tag::ALLOW_CHILDREN));
        $cdataSection = new Behavior\CdataSection();
        $behavior = $allowed ? $behavior->withNodes($cdataSection) : $behavior->withoutNodes($cdataSection);
        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    /**
     * @test
     */
    public function isJsonLdScriptAllowed(): void
    {
        $payload = implode("\n" , [
            // tag will be removed due to `PURGE_WITHOUT_ATTRS`
            '1:<script>alert(1)</script>',
            // `type` attr will be removed -> no attrs -> tag will be removed due to `PURGE_WITHOUT_ATTRS`
            '2:<script type>alert(2)</script>',
            // `type` attr will be removed -> no attrs -> tag will be removed due to `PURGE_WITHOUT_ATTRS`
            '3:<script type="application/javascript">alert(2)</script>',
            // `type` attr will be removed -> no attrs -> tag will be removed due to `PURGE_WITHOUT_ATTRS`
            '4:<script type="application/ecmascript">alert(3)</script>',
            // tag will be encoded due to incompleteness, mandatory `type` attr is missing
            '5:<script id="identifier">alert(1)</script>',
            // tag will be encoded due to incompleteness, mandatory `type` attr mismatches
            '6:<script id="identifier" type="application/javascript">alert(2)</script>',
            // tag will be removed due to `PURGE_WITHOUT_CHILDREN`
            '7:<script type="application/ld+json"></script>',
            // rest is keep, since `type` attr value matches and child content is given
            '8:<script type="application/ld+json">alert(4)</script>',
            '9:<script type="application/ld+json">{"@id": "https://github.com/TYPO3/html-sanitizer"}</script>',
        ]);
        $expectation = implode("\n" , [
            '1:',
            '2:',
            '3:',
            '4:',
            '5:&lt;script id="identifier"&gt;alert(1)&lt;/script&gt;',
            '6:&lt;script id="identifier"&gt;alert(2)&lt;/script&gt;',
            '7:',
            '8:<script type="application/ld+json">alert(4)</script>',
            '9:<script type="application/ld+json">{"@id": "https://github.com/TYPO3/html-sanitizer"}</script>',
        ]);

        $behavior = (new Behavior())
            ->withFlags(Behavior::ENCODE_INVALID_TAG | Behavior::REMOVE_UNEXPECTED_CHILDREN)
            ->withName('scenario-test')
            ->withTags(
                (new Behavior\Tag(
                    'script',
                    Behavior\Tag::PURGE_WITHOUT_ATTRS | Behavior\Tag::PURGE_WITHOUT_CHILDREN | Behavior\Tag::ALLOW_CHILDREN
                ))->addAttrs(
                    (new Behavior\Attr('id')),
                    (new Behavior\Attr('type', Behavior\Attr::MANDATORY))
                        ->addValues(new Behavior\DatasetAttrValue('application/ld+json'))
                )
            );

        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }

    /**
     * @test
     */
    public function iframeSandboxIsAllowed(): void
    {
        $payload = implode("\n" , [
            '1:<iframe src="https://example.org/"></iframe>',
            '2:<iframe src="https://example.org/" sandbox></iframe>',
            '3:<iframe src="https://example.org/" sandbox=""></iframe>',
            // `sandbox` will be removed, since token is not valid
            '4:<iframe src="https://example.org/" sandbox="allow-non-existing-property"></iframe>',
            '5:<iframe src="https://example.org/" allow="fullscreen" sandbox="allow-downloads allow-modals"></iframe>',
            '6:<iframe src="https://example.org/" sandbox="allow-downloads allow-modals allow-orientation-lock allow-pointer-lock allow-popups allow-scripts"></iframe>',
        ]);
        $expectation = implode("\n" , [
            '1:&lt;iframe src="https://example.org/"&gt;&lt;/iframe&gt;',
            '2:<iframe src="https://example.org/" sandbox></iframe>',
            '3:<iframe src="https://example.org/" sandbox></iframe>',
            '4:&lt;iframe src="https://example.org/"&gt;&lt;/iframe&gt;',
            '5:<iframe src="https://example.org/" allow="fullscreen" sandbox="allow-downloads allow-modals"></iframe>',
            '6:<iframe src="https://example.org/" sandbox="allow-downloads allow-modals allow-orientation-lock allow-pointer-lock allow-popups allow-scripts"></iframe>',
        ]);

        $behavior = (new Behavior())
            ->withFlags(Behavior::ENCODE_INVALID_TAG | Behavior::REMOVE_UNEXPECTED_CHILDREN)
            ->withName('scenario-test')
            ->withTags(
                (new Behavior\Tag('iframe'))->addAttrs(
                    (new Behavior\Attr('id')),
                    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe#attr-allow
                    (new Behavior\Attr('allow'))->withValues(
                        new Behavior\MultiTokenAttrValue(' ', 'fullscreen')
                    ),
                    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe#attr-sandbox
                    (new Behavior\Attr('sandbox', Behavior\Attr::MANDATORY))->withValues(
                        new Behavior\EmptyAttrValue(),
                        new Behavior\MultiTokenAttrValue(
                            ' ',
                            'allow-downloads',
                            'allow-modals',
                            'allow-orientation-lock',
                            'allow-pointer-lock',
                            'allow-popups',
                            'allow-scripts'
                        )
                    ),
                    (new Behavior\Attr('src'))->withValues(
                        ...(new UriAttrValueBuilder())->allowSchemes('http', 'https')->getValues()
                    )
                )
            );

        $sanitizer = new Sanitizer(
            new CommonVisitor($behavior)
        );
        self::assertSame($expectation, $sanitizer->sanitize($payload));
    }
}
