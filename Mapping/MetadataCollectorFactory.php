<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;

/**
 * Factory class for metadata collector.
 */
class MetadataCollectorFactory
{
    /**
     * @var array
     */
    protected $bundles;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var bool
     */
    protected $debug = true;

    /**
     * @param array  $bundles
     * @param string $cacheDir
     */
    public function __construct(array $bundles, $cacheDir)
    {
        $this->bundles = $bundles;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Returns Mapping collector instance.
     *
     * @return MetadataCollector
     */
    public function get()
    {
        return new MetadataCollector(
            $this->bundles,
            $this->getCacheReader()
        );
    }

    /**
     * Sets debug flag for reader.
     *
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Returns cached reader instance.
     *
     * @return FileCacheReader
     */
    private function getCacheReader()
    {
        return new FileCacheReader(
            new AnnotationReader(),
            $this->cacheDir . DIRECTORY_SEPARATOR . '/annotations',
            $this->debug
        );
    }
}
