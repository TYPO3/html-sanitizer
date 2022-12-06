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

namespace TYPO3\HtmlSanitizer\Serializer;

use DOMNode;
use Masterminds\HTML5\Serializer\RulesInterface as MastermindsRulesInterface;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\InitiatorInterface;

interface RulesInterface extends MastermindsRulesInterface
{
    /**
     * @return self
     */
    public function withBehavior(Behavior $behavior);

    /**
     * @param InitiatorInterface|null $initiator
     * @return self
     */
    public function withInitiator($initiator);

    /**
     * @return void
     */
    public function traverse(DOMNode $domNode);

    /**
     * @return resource
     */
    public function getStream();

    public function getOptions(): array;
}
