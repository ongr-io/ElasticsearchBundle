<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;

/**
 * This class converts array to document object.
 */
class Converter
{
    /**
     * @var array
     */
    private $typesMapping;

    /**
     * @var array
     */
    private $bundlesMapping;

    /**
     * Constructor.
     *
     * @param array $typesMapping
     * @param array $bundlesMapping
     */
    public function __construct($typesMapping, $bundlesMapping)
    {
        $this->typesMapping = $typesMapping;
        $this->bundlesMapping = $bundlesMapping;
    }

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return DocumentInterface Document
     * @throws \LogicException
     */
    public function convertToDocument($rawData)
    {
        if (!isset($this->typesMapping[$rawData['_type']])) {
            throw new \LogicException("Got document of unknown type '{$rawData['_type']}'.");
        }

        $metadata = $this->bundlesMapping[$this->typesMapping[$rawData['_type']]];

        $data = isset($rawData['_source']) ? $rawData['_source'] : array_map('reset', $rawData['fields']);

        /** @var DocumentInterface $object */
        $object = $this->assignArrayToObject(
            $data,
            new $metadata['namespace'](),
            array_merge_recursive($metadata['properties'], $metadata['setters'])
        );

        isset($rawData['_id']) && $object->setId($rawData['_id']);
        isset($rawData['_score']) && $object->setScore($rawData['_score']);
        isset($rawData['highlight']) && $object->setHighlight(new DocumentHighlight($rawData['highlight']));
        isset($rawData['fields']['_parent']) && $object->setParent($rawData['fields']['_parent']);
        isset($rawData['fields']['_ttl']) && $object->setTtl($rawData['fields']['_ttl']);

        return $object;
    }

    /**
     * Assigns all properties to object.
     *
     * @param array  $array
     * @param object $object
     * @param array  $setters
     *
     * @return object
     */
    public function assignArrayToObject(array $array, $object, array $setters)
    {
        foreach ($setters as $key => $setter) {
            if (!isset($array[$key])) {
                continue;
            }
            $value = $array[$key];

            if (isset($setter['properties'])) {
                if ($setter['multiple']) {
                    $value = new ObjectIterator($this, $value, $setter);
                } else {
                    $value = $this->assignArrayToObject($value, new $setter['namespace'](), $setter['properties']);
                }
            }

            if ($setter['type'] === 'date') {
                $value = \DateTime::createFromFormat(\DateTime::ISO8601, $value);
            }

            if ($setter['type'] === 'completion') {
                $value = $this->handleSuggester($setter, $value);
            }

            if ($setter['exec']) {
                $object->{$setter['name']}($value);
            } else {
                $object->{$setter['name']} = $value;
            }
        }

        return $object;
    }

    /**
     * Returns a suggester to set.
     *
     * @param array $setter
     * @param array $value
     */
    private function handleSuggester($setter, $value)
    {
    }
}
