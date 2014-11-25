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
 * Abstract record document for various suggesters.
 */
abstract class AbstractSuggester
{
    /**
     * Input to store.
     *
     * @var string[]
     */
    private $input;

    /**
     * Setter for input to store.
     *
     * @param string[]|string $input
     */
    public function setInput($input)
    {
        $this->input = is_array($input) ? $input : [$input];
    }

    /**
     * Returns input to check for.
     *
     * @return string[]
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Returns suggester type.
     *
     * @return string
     */
    abstract public function getType();
}
