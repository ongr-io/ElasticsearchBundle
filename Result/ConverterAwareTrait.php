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
 * Trait ConvertedAwareTrain.
 */
trait ConverterAwareTrait
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * @return Converter
     */
    protected function getConverter()
    {
        if ($this->converter === null) {
            $this->converter = new Converter($this->getTypesMapping(), $this->getBundlesMapping());
        }

        return $this->converter;
    }

    /**
     * Converts raw array to document.
     *
     * @param array $rawDocument
     *
     * @return DocumentInterface
     */
    protected function convertDocument($rawDocument)
    {
        return $this->getConverter()->convertToDocument($rawDocument);
    }

    /**
     * @return array
     */
    abstract protected function getTypesMapping();

    /**
     * @return array
     */
    abstract protected function getBundlesMapping();
}
