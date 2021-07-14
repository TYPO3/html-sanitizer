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

        $this->globalAttrs = $this->createGlobalAttrs();
        $this->srcAttr = (new Behavior\Attr('src', Behavior\Attr::MATCH_FIRST_VALUE))
            // @todo consider adding `data:` check
            ->addValues($isHttpOrLocalUri);
        $this->srcsetAttr = (new Behavior\Attr('srcset', Behavior\Attr::MATCH_FIRST_VALUE))
            // @todo consider adding `data:` check
            // @todo Add test for `srcset="media.png 1080w"`
            ->addValues($isHttpOrLocalUri);
        $this->hrefAttr = (new Behavior\Attr('href', Behavior\Attr::MATCH_FIRST_VALUE))
            ->addValues($isHttpOrLocalUri, $isMailtoUri);
    }

    /**
     * @return \TYPO3\HtmlSanitizer\Sanitizer
     */
    public function build()
    {
        $behavior = $this->createBehavior();
        $visitor = new CommonVisitor($behavior);
        return new Sanitizer($visitor);
    }

    /**
     * @return \TYPO3\HtmlSanitizer\Behavior
     */
    protected function createBehavior()
    {
        $behavior = (new Behavior())
            ->withFlags(Behavior::ENCODE_INVALID_TAG + Behavior::REMOVE_UNEXPECTED_CHILDREN)
            ->withName('common');

        $behavior = call_user_func_array([$behavior, 'withTags'], array_values($this->createBasicTags()));
        $behavior = call_user_func_array([$behavior, 'withTags'], array_values($this->createMediaTags()));

        return $behavior;
    }

    /**
     * @return mixed[]
     */
    protected function createBasicTags()
    {
        $names = [
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#content_sectioning
            'address', 'article', 'aside', 'footer', 'header',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'main', 'nav', 'section',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#text_content
            'blockquote', 'dd', 'div', 'dl', 'dt', 'figcaption', 'li', 'ol', 'p', 'pre', 'ul',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#inline_text_semantics
            'a', 'abbr',  'b', 'bdi', 'bdo', 'cite', 'code', 'data', 'dfn', 'em', 'i', 'kbd', 'mark',
            'q', 'rb', 'rp', 'rt', 'rtc', 'ruby', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup',
            'time', 'u', 'var', 'wbr',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#demarcating_edits
            'del', 'ins',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#table_content
            'caption', 'col', 'colgroup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#forms
            'button', 'datalist', 'label', 'legend', 'meter', 'output', 'progress',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#interactive_elements
            'details', 'dialog', 'menu', 'summary',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#web_components
            // 'slot', 'template',
            // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#obsolete_and_deprecated_elements
            'acronym', 'big', 'nobr', 'tt',
        ];

        $tags = [];
        foreach ($names as $name) {
            $behavior = new Behavior\Tag($name, Behavior\Tag::ALLOW_CHILDREN);
            $behavior = call_user_func_array([$behavior, 'addAttrs'], $this->globalAttrs);
            $tags[$name] = $behavior;
        }

        $tags['a']->addAttrs($this->hrefAttr);
        $tags['a'] = call_user_func_array([$tags['a'], 'addAttrs'], $this->createAttrs('hreflang', 'rel', 'referrerpolicy', 'target', 'type'));

        $brBehavior = new Behavior\Tag('br');
        $tags['br'] = call_user_func_array([$brBehavior, 'addAttrs'], $this->globalAttrs);

        $hrBehavior = new Behavior\Tag('hr');
        $tags['hr'] = call_user_func_array([$hrBehavior, 'addAttrs'], $this->globalAttrs);

        $tags['label'] = call_user_func_array([$tags['label'], 'addAttrs'], $this->createAttrs('for'));
        $tags['td'] = call_user_func_array([$tags['td'], 'addAttrs'], $this->createAttrs('colspan', 'rowspan', 'scope'));
        $tags['th'] = call_user_func_array([$tags['th'], 'addAttrs'], $this->createAttrs('colspan', 'rowspan', 'scope'));
        return $tags;
    }

    /**
     * @return Behavior\Attr[]
     */
    protected function createMediaTags()
    {
        $tags = [];
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#image_and_multimedia
        $tags['audio'] = (new Behavior\Tag('audio', Behavior\Tag::ALLOW_CHILDREN))->addAttrs($this->srcAttr);
        $tags['audio'] = call_user_func_array([$tags['audio'], 'addAttrs'], $this->globalAttrs);
        $tags['audio'] = call_user_func_array([$tags['audio'], 'addAttrs'], $this->createAttrs('autoplay', 'controls', 'loop', 'muted', 'preload'));

        $tags['video'] = (new Behavior\Tag('video', Behavior\Tag::ALLOW_CHILDREN))->addAttrs($this->srcAttr);
        $tags['video'] = call_user_func_array([$tags['video'], 'addAttrs'], $this->globalAttrs);
        $tags['video'] = call_user_func_array([$tags['video'], 'addAttrs'], $this->createAttrs('autoplay', 'controls', 'height', 'loop', 'muted', 'playsinline', 'poster', 'preload', 'width'));

        $tags['img'] = (new Behavior\Tag('img', Behavior\Tag::PURGE_WITHOUT_ATTRS))->addAttrs($this->srcAttr, $this->srcsetAttr);
        $tags['img'] = call_user_func_array([$tags['img'], 'addAttrs'], $this->globalAttrs);
        $tags['img'] = call_user_func_array([$tags['img'], 'addAttrs'], $this->createAttrs('alt', 'decoding', 'height', 'sizes', 'width'));

        $tags['track'] = (new Behavior\Tag('track', Behavior\Tag::PURGE_WITHOUT_ATTRS))->addAttrs($this->srcAttr);
        $tags['track'] = call_user_func_array([$tags['track'], 'addAttrs'], $this->globalAttrs);
        $tags['img'] = call_user_func_array([$tags['img'], 'addAttrs'], $this->createAttrs('default', 'kind', 'label', 'srcLang'));

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#embedded_content
        $tags['picture'] = (new Behavior\Tag('picture', Behavior\Tag::ALLOW_CHILDREN));
        $tags['picture'] = call_user_func_array([$tags['picture'], 'addAttrs'], $this->globalAttrs);

        $tags['source'] = (new Behavior\Tag('source', Behavior\Tag::ALLOW_CHILDREN));
        $tags['source'] = call_user_func_array([$tags['source'], 'addAttrs'], $this->globalAttrs);
        return array_values($tags);
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
