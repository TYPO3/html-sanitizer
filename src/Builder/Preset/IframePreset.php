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

namespace TYPO3\HtmlSanitizer\Builder\Preset;

use TYPO3\HtmlSanitizer\Behavior;

/**
 * Preset for `<iframe>` element.
 */
class IframePreset implements PresetInterface
{
    public function applyTo(Behavior $behavior, int $flags = 0): Behavior
    {
        return $behavior->withTags(
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
                    ...(new Behavior\Attr\UriAttrValueBuilder())
                        ->allowSchemes('http', 'https')->getValues()
                )
            )
        );
    }
}
