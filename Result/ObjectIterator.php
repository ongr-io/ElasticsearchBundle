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
use ONGR\ElasticsearchBundle\Mapping\Converter;

/**
 * This is for embedded ObjectType's or NestedType's iterator implemented with a lazy loading.
 */
class ObjectIterator extends AbstractLazyCollection
{
    private $converter;
    protected $collection;
    private $namespace;

    public function __construct(string $namespace, array $array, Converter $converter)
    {
        $this->converter = $converter;
        $this->collection = new ArrayCollection($array);
        $this->namespace = $namespace;
    }

    protected function convertDocument(array $data)
    {
        return $this->converter->convertArrayToDocument($this->namespace, $data);
    }

    protected function doInitialize()
    {
        $this->collection = $this->collection->map(function ($rawObject) {
            return $this->convertDocument($rawObject);
        });
    }
}
