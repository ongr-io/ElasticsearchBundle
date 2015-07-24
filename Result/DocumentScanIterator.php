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
 * Class DocumentScanIterator.
 */
class DocumentScanIterator extends AbstractConvertibleResultIterator implements \Iterator, \Countable
{
    use CountableTrait;
    use ScrollableTrait;
    use ConverterAwareTrait;

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
     * @param array $rawData
     * @param array $typesMapping
     * @param array $bundlesMapping
     */
    public function __construct($rawData, $typesMapping, $bundlesMapping)
    {
        parent::__construct($rawData);

        $this->typesMapping = $typesMapping;
        $this->bundlesMapping = $bundlesMapping;
    }

    /**
     * @return array
     */
    protected function getTypesMapping()
    {
        return $this->typesMapping;
    }

    /**
     * @return array
     */
    protected function getBundlesMapping()
    {
        return $this->bundlesMapping;
    }
}
