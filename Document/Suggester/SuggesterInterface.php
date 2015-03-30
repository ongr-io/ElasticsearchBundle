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
 * Interface for a basic suggester.
 */
interface SuggesterInterface
{
    /**
     * Setter for input to store.
     *
     * @param string $input
     */
    public function setInput($input);

    /**
     * Returns stored input.
     *
     * @return string|string[]
     */
    public function getInput();

    /**
     * Setter for string to return.
     *
     * @param string $output
     */
    public function setOutput($output);

    /**
     * Returns output to be set.
     *
     * @return string
     */
    public function getOutput();

    /**
     * Returns object to be returned in the suggest option.
     *
     * @return int|string
     */
    public function getWeight();

    /**
     * Setter for a weight used to rank suggestions.
     *
     * @param int|string $weight
     */
    public function setWeight($weight);

    /**
     * Returns object to be returned in the suggest option.
     *
     * @return object
     */
    public function getPayload();

    /**
     * Setter for object to be returned in the suggest option.
     *
     * @param object $payload
     */
    public function setPayload($payload);
}
