# TYPO3 HTML Sanitizer

> :information_source: Common safe HTML tags & attributes as given in
> [`\TYPO3\HtmlSanitizer\Builder\CommonBuilder`](src/Builder/CommonBuilder.php)
> still might be adjusted, extended or rearranged to more specific builders.

## In a Nutshell

This `typo3/html-sanitizer` package aims to be a standalone component that can be used by any PHP-based
project or library. Albeit it is released within the TYPO3 namespace, it is agnostic to specifics of
[TYPO3 CMS](https://github.com/typo3/typo3).

+ [`\TYPO3\HtmlSanitizer\Behavior`](src/Behavior.php) contains declarative settings for
  a particular process for sanitizing HTML.
+ [`\TYPO3\HtmlSanitizer\Visitor\VisitorInterface`](src/Visitor/VisitorInterface.php)
  (multiple different visitors can exist at the same time) are actually doing the work
  based on the declared `Behavior`. Visitors can modify nodes or mark them for deletion.
+ [`\TYPO3\HtmlSanitizer\Sanitizer`](src/Sanitizer.php) can be considered as the working
  instance, invoking visitors, parsing and serializing HTML. In general this instance does
  not contain much logic on how to handle particular nodes, attributes or values
+ [`\TYPO3\HtmlSanitizer\Builder\BuilderInterface`](src/Builder/BuilderInterface.php) can
  be used to create multiple different builder instances - in terms of "presets" - which
  combine declaring a particular `Behavior`, initialization of `VisitorInterface` instances,
  and finally returning a ready-to-use `Sanitizer` instance

## Installation

```bash
composer req typo3/html-sanitizer
```

## Example & API

```php
<?php
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

require_once 'vendor/autoload.php';

$commonAttrs = [
    new Behavior\Attr('id'),
    new Behavior\Attr('class'),
    new Behavior\Attr('data-', Behavior\Attr::NAME_PREFIX),
];
$hrefAttr = (new Behavior\Attr('href'))
    ->addValues(new Behavior\RegExpAttrValue('#^https?://#'));

// attention: only `Behavior` implementation uses immutability
// (invoking `withFlags()` or `withTags()` returns new instance)
$behavior = (new Behavior())
    ->withFlags(Behavior::ENCODE_INVALID_TAG)
    ->withTags(
        (new Behavior\Tag('div', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs(...$commonAttrs),
        (new Behavior\Tag('a', Behavior\Tag::ALLOW_CHILDREN))
            ->addAttrs($hrefAttr, ...$commonAttrs),
        (new Behavior\Tag('br'))
    );

$visitors = [new CommonVisitor($behavior)];
$sanitizer = new Sanitizer(...$visitors);

$html = <<< EOH
<div id="main">
    <a href="https://typo3.org/" data-type="url" wrong-attr="is-removed">TYPO3</a><br>
    (the <span>SPAN, SPAN, SPAN</span> tag shall be encoded to HTML entities)
</div>
EOH;

echo $sanitizer->sanitize($html);
```

will result in the following sanitized output

```html
<div id="main">
    <a href="https://typo3.org/" data-type="url">TYPO3</a><br>
    (the &lt;span&gt;SPAN, SPAN, SPAN&lt;/span&gt; tag shall be encoded to HTML entities)
</div>
```

### `Behavior` flags

* `Behavior::ENCODE_INVALID_TAG` keeps invalid tags, but "disarms" them (see `<span>` in example)
* `Behavior::ENCODE_INVALID_ATTR` keeps invalid attributes, but "disarms" the whole(!) tag
* `Behavior::REMOVE_UNEXPECTED_CHILDREN` removes children for `Tag` entities that were created
  without explicitly using `Tag::ALLOW_CHILDREN`, but actually contained child nodes
* `Behavior::ALLOW_CUSTOM_ELEMENTS` allow using custom elements (having a hyphen `-`) - however,
  it is suggested to explicitly name all known and allowed tags and avoid using this flag

## License

In general the TYPO3 core is released under the GNU General Public License version
2 or any later version (`GPL-2.0-or-later`). In order to avoid licensing issues and
incompatibilities this package is licenced under the MIT License. In case  you
duplicate or modify source code, credits are not required but really appreciated.

## Security Contact

In case of finding additional security issues in the TYPO3 project or in this package  in particular,
please get in touch with the [TYPO3 Security Team](mailto:security@typo3.org).
