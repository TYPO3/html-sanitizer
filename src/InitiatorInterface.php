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

namespace TYPO3\HtmlSanitizer;

/**
 * Contractor for initiators, used to keep track of of origins
 * of sanitization invocations. Data is forwared to logger (as string).
 */
interface InitiatorInterface
{
    public function __toString(): string;
}
