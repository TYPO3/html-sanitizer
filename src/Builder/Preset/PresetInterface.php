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
 * Interface for applying a preset declaration to an existing behavior.
 */
interface PresetInterface
{
    /**
     * @param Behavior $behavior to be modified
     * @param int $flags (currently not used, future topics such as `override`)
     * @return Behavior having the preset applied
     */
    public function applyTo(Behavior $behavior, int $flags): Behavior;
}
