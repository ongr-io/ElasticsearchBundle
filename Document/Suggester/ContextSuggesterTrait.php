<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Document\Suggester;

/**
 * Class to be used for context suggestion objects.
 *
 * @deprecated use Ongr\ElasticsearchBundle\Document\Suggester\AbstractSuggester
 * and implement ContextSuggesterInterface, will be removed in 1.0.
 */
trait ContextSuggesterTrait
{
    use SuggesterTrait;

    /**
     * Returns context to be used for completion.
     *
     * @return object
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets context to be used for completion.
     *
     * @param object $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}
