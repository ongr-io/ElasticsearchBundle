<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping\Proxy;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Loads proxy documents.
 */
class ProxyLoader
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var bool
     */
    private $debug;

    /**
     * Constructor.
     *
     * @param string $cacheDir To load proxies to.
     * @param bool   $debug    Whether debugging is enabled or not.
     */
    public function __construct($cacheDir, $debug = false)
    {
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    /**
     * Loads document proxy class into cache.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return string Proxy document path.
     */
    public function load(\ReflectionClass $reflectionClass)
    {
        $cacheBundleDir = $this->getCacheDir($reflectionClass->getName());

        $cache = new ConfigCache(
            $cacheBundleDir . DIRECTORY_SEPARATOR . md5(strtolower($reflectionClass->getShortName())) . '.php',
            $this->debug
        );

        if (!$cache->isFresh()) {
            $code = ProxyFactory::generate($reflectionClass);
            $cache->write($code, [new FileResource($reflectionClass->getFileName())]);
        }

        return $cache->__toString();
    }

    /**
     * Returns cache directory.
     *
     * @param string $namespace Real document namespace.
     *
     * @return string
     */
    private function getCacheDir($namespace)
    {
        if (substr($namespace, -6) !== 'Bundle') {
            return $this->getCacheDir(substr($namespace, 0, strrpos($namespace, '\\')));
        }

        return $this->cacheDir . DIRECTORY_SEPARATOR . strtolower(substr($namespace, strrpos($namespace, '\\') + 1));
    }

    /**
     * Returns Filesystem object.
     *
     * @return Filesystem
     */
    private function getFilesystem()
    {
        if (!$this->fileSystem) {
            $this->fileSystem = new Filesystem();
        }

        return $this->fileSystem;
    }
}
