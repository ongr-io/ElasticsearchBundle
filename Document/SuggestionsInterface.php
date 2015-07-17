<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document;

/**
 * Interface for a basic suggester.
 */
interface SuggestionsInterface
{
    /**
     * Setter for input to store.
     *
     * @param string $input
     *
     * @return $this
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
     *
     * @return $this
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
     *
     * @return $this
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
     *
     * @return $this
     */
    public function setPayload($payload);

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
