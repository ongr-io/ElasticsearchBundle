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

/**
 * ObjectIterator class.
 */
class ObjectIterator extends AbstractResultsIterator
{
    /**
     * @var array Aliases information.
     */
    private $alias;

    /**
     * @var Converter
     */
    private $objectConverter;

    /**
     * Using part of abstract iterator functionality only.
     *
     * @param Converter $converter
     * @param array     $documents
     * @param array     $alias
     */
    public function __construct($converter, $documents, $alias)
    {
        $this->documents = $documents;
        $this->alias = $alias;
        $this->objectConverter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $this->objectConverter->assignArrayToObject(
            $document,
            new $this->alias['namespace'](),
            $this->alias['aliases']
        );
    }

    /**
     * Return current document count.
     *
     * @return int
     */
    public function count()
    {
        return count($this->documents);
    }
}
