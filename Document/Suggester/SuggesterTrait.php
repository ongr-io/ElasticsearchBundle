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
 *
 * @deprecated use ONGR\ElasticsearchBundle\Document\Suggester\AbstractSuggester, will be removed in 1.0.
 */
trait SuggesterTrait
{
    /**
     * @var string[]|string Input to store.
     *
     * @ES\Property(type="string", name="input")
     */
    private $input;

    /**
     * @var string String to return.
     *
     * @ES\Property(type="string", name="output")
     */
    private $output;

    /**
     * @var object Object to be returned in the suggest option.
     *
     * @ES\Property(type="object", name="payload")
     */
    private $payload;

    /**
     * @var int|string Weight used to rank suggestions.
     *
     * @ES\Property(type="string", name="weight")
     */
    private $weight;

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
     * Setter for object to be returned in the suggest option.
     *
     * @param object $payload
     */
    public function setPayload($payload)
    {
        $this->payload = (object)$payload;
    }

    /**
     * Returns object to be returned in the suggest option.
     *
     * @return object
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Setter for a weight used to rank suggestions.
     *
     * @param int|string $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Returns object to be returned in the suggest option.
     *
     * @return int|string
     */
    public function getWeight()
    {
        return $this->weight;
    }
}
