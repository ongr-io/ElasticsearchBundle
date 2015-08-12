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
 * Class AbstractConvertibleResultIterator.
 */
abstract class AbstractConvertibleResultIterator extends AbstractResultsIterator
{
    /**
     * @var array
     */
    private $converted = [];

    /**
     * {@inheritdoc}
     */
    protected function getDocument($key)
    {
        if (!$this->documentExists($key)) {
            return null;
        }
        if (!$this->getStoreConverted()) {
            return $this->convertDocument(parent::getDocument($key));
        }

        if (!array_key_exists($key, $this->converted)) {
            $this->converted[$key] = $this->convertDocument(parent::getDocument($key));
            $this->clearDocument($key);
        }

        return $this->converted[$key];
    }

    /**
     * {@inheritdoc}
     */
    protected function addDocument($document, $key)
    {
        if (is_object($document)) {
            if ($key === null) {
                $this->converted[] = $document;
            } else {
                $this->converted[$key] = $document;
            }
            parent::addDocument(null, $key);
        } else {
            if (isset($this->converted[$key])) {
                unset($this->converted[$key]);
            }
            parent::addDocument($document, $key);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeDocument($key)
    {
        unset($this->converted[$key]);
        parent::removeDocument($key);

        return $this;
    }

    /**
     * Removes set documents.
     *
     * @return $this
     */
    protected function clean()
    {
        parent::clean();
        $this->converted = [];

        return $this;
    }

    /**
     * Converts raw document to object.
     *
     * @param mixed $document
     *
     * @return mixed
     */
    abstract protected function convertDocument($document);

    /**
     * @return bool
     */
    protected function getStoreConverted()
    {
        return true;
    }
}
