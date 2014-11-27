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
     * @var string[]|string
     */
    private $input;

    /**
     * String to return.
     *
     * @var string
     */
    private $output;

    /**
     * Setter for input to store.
     *
     * @param string[]|string $input
     */
    public function setInput($input)
    {
        $this->input = $input;
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
     * Setter for string to return.
     *
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Returns output to be set.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Returns suggester type.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * Returns array value of this suggester.
     *
     * @return array
     */
    public function toArray()
    {
        $out = [];

        if ($this->getInput() !== null) {
            $out['input'] = $this->getInput();
        }

        if ($this->getOutput() !== null) {
            $out['output'] = $this->getOutput();
        }

        return $out;
    }

    /**
     * Sets object fields using the array passed.
     *
     * @param array $rawArray
     */
    public function fromArray($rawArray)
    {
    }
}
