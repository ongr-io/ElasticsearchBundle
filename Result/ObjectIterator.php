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

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ObjectIterator class.
 */
class ObjectIterator extends AbstractLazyCollection
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var array Aliases information.
     */
    private $alias;

    /**
     * Converts raw document data to objects when requested.
     *
     * @param Converter $converter
     * @param array     $objects
     * @param array     $alias
     */
    public function __construct($converter, $objects, $alias)
    {
        $this->converter = $converter;
        $this->alias = $alias;
        $this->collection = new ArrayCollection($objects);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $this->converter->assignArrayToObject(
            $document,
            new $this->alias['namespace'](),
            $this->alias['aliases']
        );
    }

    /**
     * @inheritDoc
     */
    protected function doInitialize()
    {
        $this->collection = $this->collection->map(function ($rawObject) {
            return $this->convertDocument($rawObject);
        });
    }
}
