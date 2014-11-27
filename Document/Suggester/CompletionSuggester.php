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
 */
abstract class CompletionSuggester extends AbstractSuggester
{
    /**
     * Object to be returned in the suggest option.
     *
     * @var array
     */
    private $payload;

    /**
     * Weight used to rank suggestions.
     *
     * @var int|string
     */
    private $weight;

    /**
     * Setter for object to be returned in the suggest option.
     *
     * @param array $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Returns object to be returned in the suggest option.
     *
     * @return array
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

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $out = parent::toArray();

        if ($this->getWeight() !== null) {
            $out['weight'] = $this->getWeight();
        }

        if ($this->getPayload() !== null) {
            $out['payload'] = $this->getPayload();
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
