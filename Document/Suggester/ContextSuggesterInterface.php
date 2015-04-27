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

/**
 * Interface to be used for completion suggestion objects.
 */
interface ContextSuggesterInterface extends SuggesterInterface
{
    /**
     * Returns context to be used for completion.
     *
     * @return object
     */
    public function getContext();

    /**
     * Sets context to be used for completion.
     *
     * @param object $context
     */
    public function setContext($context);
}
