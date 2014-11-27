<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document\Suggester;

use ONGR\ElasticsearchBundle\Document\Suggester\Context\AbstractContext;

/**
 * Class to be used for context suggestion objects.
 */
class ContextSuggester extends AbstractSuggester
{
    /**
     * Contexts for context suggester.
     *
     * @var AbstractContext[]
     */
    private $contexts;

    /**
     * @return AbstractContext[]
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @param AbstractContext[] $contexts
     */
    public function setContexts($contexts)
    {
        $this->contexts = $contexts;
    }

    /**
     * @param AbstractContext $context
     */
    public function addContext(AbstractContext $context)
    {
        $this->contexts[] = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $out = parent::toArray();

        foreach ($this->contexts as $context) {
            $out['context'][$context->getName()] = $context->getValue();
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'completion';
    }
}
