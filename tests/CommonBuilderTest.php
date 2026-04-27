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
    public static function isSanitizedDataProvider(): iterable
    {
        yield '#010' => [
            '<unknown unknown="unknown">value</unknown>',
            '&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;',
        ];
        yield '#011' => [
            '<div class="nested"><unknown unknown="unknown">value</unknown></div>',
            '<div class="nested">&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;</div>',
        ];
        yield '#012' => [
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            '&lt;script&gt;alert(1)&lt;/script&gt;',
        ];
        yield '#013' => [
            '<unknown unknown="unknown">value</unknown>' .
                '<unknown unknown="unknown">value</unknown>',
            '&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;' .
                '&lt;unknown unknown="unknown"&gt;value&lt;/unknown&gt;'
        ];
        yield '#014' => [
            '<unknown>value</unknown><unknown>value</unknown>' .
                '<div unknown="unknown">value</div>' .
                '<unknown>value</unknown><unknown>value</unknown>',
            '&lt;unknown&gt;value&lt;/unknown&gt;&lt;unknown&gt;value&lt;/unknown&gt;' .
                '<div>value</div>' .
                '&lt;unknown&gt;value&lt;/unknown&gt;&lt;unknown&gt;value&lt;/unknown&gt;'
        ];
        yield '#015' => [
            '<unknown unknown="unknown" class="nested"><div class="nested">value</div></unknown>',
            // '&lt;unknown unknown="unknown" class="nested"&gt;<div class="nested">value</div>&lt;/unknown&gt;',
            // @todo invalidating nested nodes due invalid parent node is currently expected - topic for discussion
            '&lt;unknown unknown="unknown" class="nested"&gt;&lt;div class="nested"&gt;value&lt;/div&gt;&lt;/unknown&gt;',
        ];
        // @todo bug in https://github.com/Masterminds/html5-php/issues
        // '#013' => [
        //    '<strong>Given that x < y and y > z...</strong>',
        //    '<strong>Given that x &lt; y and y &gt; z...</strong>',
        // ],
        yield '#020' => [
            '<div unknown="unknown">value</div>',
            '<div>value</div>',
        ];
        yield '#030' => [
            '<div class="class">value</div>',
            '<div class="class">value</div>',
        ];
        yield '#031' => [
            '<div data-value="value">value</div>',
            '<div data-value="value">value</div>',
        ];
        yield '#032' => [
            '<div data-bool>value</div>',
            '<div data-bool>value</div>',
        ];
        yield '#040' => [
            '<img src="mailto:noreply@typo3.org" onerror="alert(1)">',
            '',
        ];
        yield '#041' => [
            '<img src="https://typo3.org/logo.svg" onerror="alert(1)">',
            '<img src="https://typo3.org/logo.svg">',
        ];
        yield '#042' => [
            '<img src="http://typo3.org/logo.svg" onerror="alert(1)">',
            '<img src="http://typo3.org/logo.svg">',
        ];
        yield '#043' => [
            '<img src="/typo3.org/logo.svg" onerror="alert(1)">',
            '<img src="/typo3.org/logo.svg">',
        ];
        yield '#044' => [
            '<img src="typo3.org/logo.svg" onerror="alert(1)">',
            '<img src="typo3.org/logo.svg">',
        ];
        yield '#045' => [
            '<img src="//typo3.org/logo.svg" onerror="alert(1)">',
            '',
        ];
        yield '#046' => [
            '<img src="/typo3.org/logo.svg" alt="logo" loading="lazy" fetchpriority="low" decoding="async" width="100" height="100" sizes="33.3vw" name="logo" align="left" border="0">',
            '<img src="/typo3.org/logo.svg" alt="logo" loading="lazy" fetchpriority="low" decoding="async" width="100" height="100" sizes="33.3vw" name="logo" align="left" border="0">',
        ];
        yield '#047' => [
            '<img src="data:text/html,<script>alert(1)</script>">',
            '',
        ];
        yield '#048' => [
            '<img src="data:image/png,..."><img src="data:image/png;,..."><img src="data:image/png;base64,..."><img src="data:image/svg+xml;base64,...">',
            '<img src="data:image/png,..."><img src="data:image/png;,..."><img src="data:image/png;base64,..."><img src="data:image/svg+xml;base64,...">',
        ];
        yield '#049' => [
            '<a href="git://github.com/typo3/typo3">GitHub</a>',
            '<a href="git://github.com/typo3/typo3">GitHub</a>',
        ];
        yield '#050' => [
            '<a href="https://typo3.org/" role="button">value</a>',
            '<a href="https://typo3.org/" role="button">value</a>',
        ];
        yield '#051' => [
            '<a href="ssh://example.org/" role="button">value</a>',
            '<a role="button">value</a>',
        ];
        yield '#052' => [
            '<a href="javascript:alert(1)" role="button">value</a>',
            '<a role="button">value</a>',
        ];
        yield '#053' => [
            '<a href="data:text/html;..." role="button">value</a>',
            '<a role="button">value</a>',
        ];
        yield '#054' => [
            '<a href="#anchor">anchor</a><a name="anchor">content</a>',
            '<a href="#anchor">anchor</a><a name="anchor">content</a>',
        ];
        yield '#055' => [
            '<a href="tel:123456789">anchor</a>',
            '<a href="tel:123456789">anchor</a>',
        ];
        yield '#090' => [
            '<p data-bool><span data-bool><strong data-bool>value</strong></span></p>',
            '<p data-bool><span data-bool><strong data-bool>value</strong></span></p>'
        ];
        yield '#100' => [
            '<table><caption>c</caption><thead><tr><th>h</th></tr></thead><tbody><tr><td>b</td></tr></tbody><tfoot><tr><td>f</td></tr></tfoot></table>',
            '<table><caption>c</caption><thead><tr><th>h</th></tr></thead><tbody><tr><td>b</td></tr></tbody><tfoot><tr><td>f</td></tr></tfoot></table>',
        ];
        yield '#101' => [
            '<table align="left" border="2" cellpadding="2" cellspacing="2" class="table" summary="summary"></table>',
            '<table align="left" border="2" cellpadding="2" cellspacing="2" class="table" summary="summary"></table>',
        ];
        yield '#102' => [
            '<caption align="left">caption</caption>',
            '<caption align="left">caption</caption>',
        ];
        yield '#104' => [
            '<tr align="left" valign="top" bgcolor="#cc0000"><td>td</td></tr>',
            '<tr align="left" valign="top" bgcolor="#cc0000"><td>td</td></tr>',
        ];
        yield '#105' => [
            '<td abbr="abbr" align="left" valign="top" colspan="2" rowspan="2" bgcolor="#cc0000" axis="axis,axis" headers="head,head" scope="scope" width="100" height="100">value</td>',
            '<td abbr="abbr" align="left" valign="top" colspan="2" rowspan="2" bgcolor="#cc0000" axis="axis,axis" headers="head,head" scope="scope" width="100" height="100">value</td>',
        ];
        yield '#106' => [
            '<thead align="left" valign="top" bgcolor="#cc0000"><td>td</td></thead>',
            '<thead align="left" valign="top" bgcolor="#cc0000"><td>td</td></thead>',
        ];
        yield '#107' => [
            '<thead align="left" valign="top" bgcolor="#cc0000"><td>td</td></thead>',
            '<thead align="left" valign="top" bgcolor="#cc0000"><td>td</td></thead>',
        ];
        yield '#108' => [
            '<tfoot align="left" valign="top" bgcolor="#cc0000"><td>td</td></tfoot>',
            '<tfoot align="left" valign="top" bgcolor="#cc0000"><td>td</td></tfoot>',
        ];
        yield '#109' => [
            '<colgroup align="left" valign="top" bgcolor="#cc0000" span="1"><col><col span="2" align="left" valign="top" bgcolor="#cc0000" width="100"></colgroup>',
            '<colgroup align="left" valign="top" bgcolor="#cc0000" span="1"><col><col span="2" align="left" valign="top" bgcolor="#cc0000" width="100"></colgroup>',
        ];
        yield '#120' => [
            '<figure><img src="https://typo3.org/logo.svg" alt="logo"><figcaption>TYPO3 logo</figcaption></figure>',
            '<figure><img src="https://typo3.org/logo.svg" alt="logo"><figcaption>TYPO3 logo</figcaption></figure>',
        ];
        yield '#121' => [
            '<picture><source srcset="/logo-800.png" media="(min-width: 800px)" type="image/png" sizes="33.3vw"></picture>',
            '<picture><source srcset="/logo-800.png" media="(min-width: 800px)" type="image/png" sizes="33.3vw"></picture>'
        ];
        yield '#122' => [
            '<video controls src="/video.mp4"><track default kind="captions" srclang="en" src="/video.vtt"></video>',
            '<video controls src="/video.mp4"><track default kind="captions" srclang="en" src="/video.vtt"></video>',
        ];
        yield '200' => [
            '<ul><li>item</li><li>item</li></ul>',
            '<ul><li>item</li><li>item</li></ul>',
        ];
        yield '201' => [
            '<ol reversed start="3" type="I"><li>item</li><li value="13">item</li></ol>',
            '<ol reversed start="3" type="I"><li>item</li><li value="13">item</li></ol>',
        ];
        yield '#900' => [
            '<div id="main">' .
                '<a href="https://typo3.org/" data-type="url" wrong-attr="is-removed">TYPO3</a><br>' .
                '(the <script>alert(1)</script> tag shall be encoded to HTML entities)'.
            '</div>',
            '<div id="main">' .
                '<a href="https://typo3.org/" data-type="url">TYPO3</a><br>' .
                '(the &lt;script&gt;alert(1)&lt;/script&gt; tag shall be encoded to HTML entities)'.
            '</div>',
        ];
        yield '#901' => [
            '<div itemprop="tel" itemscope>' .
                '<span itemprop="value">+1-234-56789</span>' .
                '<meta itemprop="type" content="voice">' .
            '</div>',
            '<div itemprop="tel" itemscope>' .
                '<span itemprop="value">+1-234-56789</span>' .
                '<meta itemprop="type" content="voice">' .
            '</div>'
        ];
        yield '#902' => [
            '<div><meta http-equiv="refresh" content="1;https://evil.typo3.org/" name="referrer" charset="utf-8"></div>',
            '<div></div>'
        ];
        yield '#903' => [
            '<font class="font" color="#000000" face="Verdana,Arial" size="13">value</font>',
            '<font class="font" color="#000000" face="Verdana,Arial" size="13">value</font>'
        ];
        yield '#904' => [
            '<img src="cid:DC117C9322DEB502C3B16769A8A64E08@example.test">',
            '<img src="cid:DC117C9322DEB502C3B16769A8A64E08@example.test">',
        ];
        yield '#905' => [
            '<a href="mid:D89CD33E-F9CF-4CA0-BCE3-AC89E5D41DE1@example.test/DC117C9322DEB502C3B16769A8A64E08@example.test">see previous message</a>',
            '<a href="mid:D89CD33E-F9CF-4CA0-BCE3-AC89E5D41DE1@example.test/DC117C9322DEB502C3B16769A8A64E08@example.test">see previous message</a>',
        ];
        yield '#906' => [
            '<center>value</center><strike>value</strike><nobr>value</nobr>',
            '<center>value</center><strike>value</strike><nobr>value</nobr>',
        ];
        yield '#907' => [
            '<script>alert(1)</script>'
            . '<script type="application/javascript">alert(2)</script>'
            . '<script type="application/ecmascript">alert(3)</script>',
            '&lt;script&gt;alert(1)&lt;/script&gt;'
            . '&lt;script type="application/javascript"&gt;alert(2)&lt;/script&gt;'
            . '&lt;script type="application/ecmascript"&gt;alert(3)&lt;/script&gt;'
        ];
        yield '#908' => [
            '<a href="xmpp:user@example.org?message">value</a>',
            '<a href="xmpp:user@example.org?message">value</a>',
        ];
        yield '#909' => [
            '<!-- #comment -->',
            '<!-- #comment -->',
        ];
        yield '#910' => [
            '<!-- <"comment"> -->',
            '<!-- &lt;&quot;comment&quot;&gt; -->',
        ];
        yield '#911' => [
            '<!-- &lt;&quot;comment&quot;&gt; -->',
            '<!-- &lt;&quot;comment&quot;&gt; -->',
        ];
        yield '#912' => [
            '<!---><p>',
            '<!---&gt;&lt;p&gt;-->',
        ];
        yield '#913' => [
            '<!---!><p>',
            '<!---!&gt;&lt;p&gt;-->',
        ];
        yield '#915' => [
            '#text',
            '#text',
        ];
        yield '#920' => [
            '<![CDATA[ #cdata ]]>',
            '#cdata',
        ];
        yield '#921' => [
            '<![CDATA[<any><span data-value="value"></any>*/]]>',
            '&lt;any&gt;&lt;span data-value="value"&gt;&lt;/any&gt;*/',
        ];
        yield '#930' => [
            '<br><any>value</any></br>',
            '<br>&lt;any&gt;value&lt;/any&gt;<br>',
        ];
        yield '#931' => [
            '<hr><any>value</any></hr>',
            '<hr>&lt;any&gt;value&lt;/any&gt;',
        ];
        yield '#932' => [
            '<wbr><any>value</any></wbr>',
            '<wbr>&lt;any&gt;value&lt;/any&gt;',
        ];
        yield '#933' => [
            '<source><any>value</any></source>',
            '<source>&lt;any&gt;value&lt;/any&gt;',
        ];
        yield '#934' => [
            '<img src="/typo3.org/logo.svg"><any>value</any></img>',
            '<img src="/typo3.org/logo.svg">&lt;any&gt;value&lt;/any&gt;',
        ];
        yield '#935' => [
            '<p class="</p><script>alert(1)">value</p>',
            '<p class="&lt;/p&gt;&lt;script&gt;alert(1)">value</p>',
        ];
        yield '#936' => [
            '<p class="{&quot;json&quot;:true}">value</p>',
            '<p class="{&quot;json&quot;:true}">value</p>',
        ];
        yield '#941' => [
            '<?xml >s<img src=x onerror=alert(1)> ?>',
            '&lt;?xml &gt;s&lt;img src=x onerror=alert(1)&gt; ?&gt;',

        ];
        yield '#950' => [
            '<div xmlns:x="&quot;&gt;&lt;img src=x onerror=alert(document.domain)&gt;">text</div>',
            '<div xmlns:x="&quot;&gt;&lt;img src=x onerror=alert(document.domain)&gt;">text</div>',
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
