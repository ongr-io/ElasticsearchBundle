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
class ObjectIterator extends AbstractConvertibleResultIterator implements \ArrayAccess, \Iterator, \Countable
{
    use ArrayAccessTrait;
    use IteratorTrait;
    use CountableTrait;

    /**
     * @var array Aliases information.
     */
    private $alias;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param Converter $converter
     * @param array     $rawData
     * @param array     $alias
     */
    public function __construct($converter, $rawData, $alias)
    {
        parent::__construct([]);

        $this->setDocuments($rawData);
        $this->setTotalCount(count($rawData));
        $this->converter = $converter;
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument($rawData)
    {
        return $this->converter->assignArrayToObject(
            $rawData,
            new $this->alias['namespace'](),
            $this->alias['aliases']
        );
    }
}
