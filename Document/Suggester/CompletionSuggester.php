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

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Class to be used for completion suggestion objects.
 *
 * @ES\Object
 */
class CompletionSuggester extends AbstractSuggester
{
    /**
     * String to return.
     *
     * @var string
     */
    private $output;

    /**
     * Object to be returned in the suggest option.
     *
     * @var object
     */
    private $payload;

    /**
     * Weight used to rank suggestions.
     *
     * @var int|string
     */
    private $weight;

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
        $this->payload = $payload;
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
     *
     * @return mixed
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Returns object to be returned in the suggest option.
     *
     * @return object
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'completion';
    }
}
