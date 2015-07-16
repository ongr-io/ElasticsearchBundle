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
     * Sets context to be used for completion.
     *
     * @param array $contexts Key stands for context name and value - context value.
     *
     * @return $this
     */
    public function setContext(array $contexts);

    /**
     * Returns specific context.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getContext($name = null);

    /**
     * Sets specific context.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function addContext($name, $value);

    /**
     * Removes specific context.
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeContext($name);
}
