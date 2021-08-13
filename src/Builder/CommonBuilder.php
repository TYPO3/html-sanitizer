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

namespace TYPO3\HtmlSanitizer\Builder;

use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

/**
 * Builder, creating a `Sanitizer` instance with "common"
 * behavior for tags, attributes and values.
 */
class CommonBuilder implements BuilderInterface
{
    /**
     * @var Behavior\Attr[]
     */
    protected $globalAttrs;

    /**
     * @var Behavior\Attr
     */
    protected $srcAttr;

    /**
     * @var Behavior\Attr
     */
    protected $srcsetAttr;

    /**
     * @var Behavior\Attr
     */
    protected $hrefAttr;

    public function __construct()
    {
        // + starting with `http://` or `https://`
        // + starting with `/` but, not starting with `//`
        // + not starting with `/` and not having `:` at all
        $isHttpOrLocalUri = new Behavior\RegExpAttrValue('#^(https?://|/(?!/)|[^/:][^:]*$)#');
        // + starting with `mailto:`
        $isMailtoUri = new Behavior\RegExpAttrValue('#^mailto:#');
        // + starting with `tel:`
        $isTelUri = new Behavior\RegExpAttrValue('#^tel:#');

        $this->globalAttrs = $this->createGlobalAttrs();
        $this->srcAttr = (new Behavior\Attr('src', Behavior\Attr::MATCH_FIRST_VALUE))
            // @todo consider adding `data:` check
            ->addValues($isHttpOrLocalUri);
        $this->srcsetAttr = (new Behavior\Attr('srcset', Behavior\Attr::MATCH_FIRST_VALUE))
            // @todo consider adding `data:` check
            // @todo Add test for `srcset="media.png 1080w"`
            ->addValues($isHttpOrLocalUri);
        $this->hrefAttr = (new Behavior\Attr('href', Behavior\Attr::MATCH_FIRST_VALUE))
            ->addValues($isHttpOrLocalUri, $isMailtoUri, $isTelUri);
    }

    /**
     * @return \TYPO3\HtmlSanitizer\Sanitizer
     */
    public function build()
    {
        $behavior = $this->createBehavior();
        $visitors = [new CommonVisitor($behavior)];
        return new Sanitizer($visitors);
    }

    /**
     * @return \TYPO3\HtmlSanitizer\Behavior
     */
    protected function createBehavior()
    {
        return (new Behavior())
            ->withFlags(Behavior::ENCODE_INVALID_TAG + Behavior::REMOVE_UNEXPECTED_CHILDREN)
            ->withName('common')
            ->withTags(array_values($this->createBasicTags()))
            ->withTags(array_values($this->createMediaTags()))
            ->withTags(array_values($this->createTableTags()));
    }

    /**
     * @return Behavior\Tag[]
     */
    protected function createBasicTags()
    {
        $names = [
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#content_sectioning
            'address', 'article', 'aside', 'footer', 'header',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'main', 'nav', 'section',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#text_content
            'blockquote', 'dd', 'div', 'dl', 'dt', 'figcaption', 'figure', 'li', 'ol', 'p', 'pre', 'ul',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#inline_text_semantics
            'a', 'abbr',  'b', 'bdi', 'bdo', 'cite', 'code', 'data', 'dfn', 'em', 'i', 'kbd', 'mark',
            'q', 'rb', 'rp', 'rt', 'rtc', 'ruby', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup',
            'time', 'u', 'var', 'wbr',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#demarcating_edits
            'del', 'ins',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#forms
            'button', 'datalist', 'label', 'legend', 'meter', 'output', 'progress',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#interactive_elements
            'details', 'dialog', 'menu', 'summary',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#web_components
            // 'slot', 'template',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#obsolete_and_deprecated_elements
            'acronym', 'big', 'nobr', 'tt',
        ];

        /** @var Behavior\Tag[] $tags */
        $tags = [];
        foreach ($names as $name) {
            $tags[$name] = (new Behavior\Tag($name, Behavior\Tag::ALLOW_CHILDREN))
                ->addAttrs($this->globalAttrs);
        }

        $tags['a']->addAttrs(array_merge(
            [$this->hrefAttr],
            $this->createAttrs(
                // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a
                'download', 'hreflang', 'ping', 'rel', 'referrerpolicy', 'target', 'type',
                // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#deprecated_attributes
                'charset', 'name', 'rev'
            )
        ));
        $tags['br'] = (new Behavior\Tag('br'))->addAttrs($this->globalAttrs);
        $tags['hr'] = (new Behavior\Tag('hr'))->addAttrs($this->globalAttrs);
        $tags['label']->addAttrs($this->createAttrs('for'));

        return $tags;
    }

    /**
     * @return Behavior\Tag[]
     */
    protected function createMediaTags()
    {
        $tags = [];
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#image_and_multimedia
        $tags['audio'] = (new Behavior\Tag('audio', Behavior\Tag::ALLOW_CHILDREN))->addAttrs(array_merge(
            [$this->srcAttr],
            $this->globalAttrs,
            $this->createAttrs('autoplay', 'controls', 'loop', 'muted', 'preload')
        ));
        $tags['video'] = (new Behavior\Tag('video', Behavior\Tag::ALLOW_CHILDREN))->addAttrs(array_merge(
            [$this->srcAttr],
            $this->globalAttrs,
            $this->createAttrs('autoplay', 'controls', 'height', 'loop', 'muted', 'playsinline', 'poster', 'preload', 'width')
        ));
        $tags['img'] = (new Behavior\Tag('img', Behavior\Tag::PURGE_WITHOUT_ATTRS))->addAttrs(array_merge(
            [$this->srcAttr],
            $this->globalAttrs,
            $this->createAttrs('align', 'alt', 'border', 'decoding', 'height', 'sizes', 'width', 'loading', 'name')
        ));
        $tags['track'] = (new Behavior\Tag('track', Behavior\Tag::PURGE_WITHOUT_ATTRS))->addAttrs(array_merge(
            [$this->srcAttr],
            $this->globalAttrs,
            $this->createAttrs('default', 'kind', 'label', 'srcLang')
        ));

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#embedded_content
        $tags['picture'] = (new Behavior\Tag('picture', Behavior\Tag::ALLOW_CHILDREN))->addAttrs($this->globalAttrs);
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/source
        $tags['source'] = (new Behavior\Tag('source'))->addAttrs(array_merge(
            $this->globalAttrs,
            $this->createAttrs('media', 'sizes', 'src', 'srcset', 'type')
        ));

        return $tags;
    }

    protected function createTableTags()
    {
        // // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#table_content
        $tags = [];
        // declarations related to <table> elements
        $commonTableAttrs = $this->createAttrs('align', 'valign', 'bgcolor');
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/table
        $tags['table'] = (new Behavior\Tag('table', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs))
            ->addAttrs($this->createAttrs('border', 'cellpadding', 'cellspacing', 'summary'));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/caption
        $tags['caption'] = (new Behavior\Tag('caption', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs($this->globalAttrs)
            ->addAttrs($this->createAttrs('align'));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/thead
        $tags['thead'] = (new Behavior\Tag('thead', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/tbody
        $tags['tbody'] = (new Behavior\Tag('tbody', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/tfoot
        $tags['tfoot'] = (new Behavior\Tag('tfoot', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs));
        $tags['tr'] = (new Behavior\Tag('tr', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/td
        $tags['td'] = (new Behavior\Tag('td', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs))
            ->addAttrs($this->createAttrs('abbr', 'axis', 'headers', 'colspan', 'rowspan', 'scope', 'width', 'height'));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/th
        $tags['th'] = (new Behavior\Tag('th', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs))
            ->addAttrs($this->createAttrs('colspan', 'rowspan', 'scope'));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/colgroup
        $tags['colgroup'] = (new Behavior\Tag('colgroup', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs))
            ->addAttrs($this->createAttrs('span'));
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/col
        $tags['col'] = (new Behavior\Tag('col')) // no children here
            ->addAttrs(array_merge($this->globalAttrs, $commonTableAttrs))
            ->addAttrs($this->createAttrs('span', 'width'));
        return $tags;
    }

    /**
     * @return Behavior\Attr[]
     */
    protected function createGlobalAttrs()
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes
        $attrs = $this->createAttrs(
            'class',
            'id',
            'dir',
            'lang',
            'nonce',
            'xml:lang',
            'itemid',
            'itemprop',
            'itemref',
            'itemscope',
            'itemtype',
            'role',
            'tabindex',
            'title',
            'translate'
        );
        $attrs[] = new Behavior\Attr('aria-', Behavior\Attr::NAME_PREFIX);
        $attrs[] = new Behavior\Attr('data-', Behavior\Attr::NAME_PREFIX);
        return $attrs;
    }

    /**
     * @return Behavior\Attr[]
     */
    protected function createAttrs()
    {
        return array_map(
            function ($name) {
                $name = (string) $name;
                return new Behavior\Attr($name);
            },
            func_get_args()
        );
    }
}
