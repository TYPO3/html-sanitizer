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
use Masterminds\HTML5\Serializer\OutputRules;
use Masterminds\HTML5\Serializer\Traverser;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\InitiatorInterface;

class Rules extends OutputRules implements RulesInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var ?Traverser
     */
    protected $traverser;

    /**
     * @var ?Behavior
     */
    protected $behavior;

    /**
     * @var ?InitiatorInterface
     */
    protected $initiator;

    /**
     * @param Behavior $behavior
     * @param resource$output
     * @param array $options
     * @return self
     */
    public static function create(Behavior $behavior, $output, array $options = []): self
    {
        $target = new self($output, $options);
        $target->options = $options;
        $target->behavior = $behavior;
        return $target;
    }

    /**
     * @param resource $output
     * @param array $options
     */
    public function __construct($output, $options = [])
    {
        $this->options = (array)$options;
        parent::__construct($output, $this->options);
    }

    public function withBehavior(Behavior $behavior): self
    {
        if ($this->behavior === $behavior) {
            return $this;
        }
        $target = clone $this;
        $target->behavior = $behavior;
        return $target;
    }

    /**
     * @param InitiatorInterface|null $initiator
     */
    public function withInitiator($initiator): self
    {
        if ($this->initiator === $initiator) {
            return $this;
        }
        $target = clone $this;
        $target->initiator = $initiator;
        return $target;
    }

    /**
     * @return void
     */
    public function traverse(DOMNode $domNode)
    {
        $traverser = new Traverser($domNode, $this->out, $this, $this->options);
        $traverser->walk();
        // release the traverser to avoid cyclic references and allow PHP
        // to free memory without waiting for gc_collect_cycles
        $this->unsetTraverser();
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->out;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
