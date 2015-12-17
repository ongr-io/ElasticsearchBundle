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

/**
 * Finds documents in bundles.
 */
class DocumentFinder
{
    /**
     * @var array
     */
    private $bundles;

    /**
     * @var string Directory in bundle to load documents from.
     */
    const DOCUMENT_DIR = 'Document';

    /**
     * Constructor.
     *
     * @param array $bundles Parameter kernel.bundles from service container.
     */
    public function __construct(array $bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * Formats namespace from short syntax.
     *
     * @param string $namespace
     *
     * @return string
     */
    public function getNamespace($namespace)
    {
        if (strpos($namespace, ':') !== false) {
            list($bundle, $document) = explode(':', $namespace);
            $bundle = $this->getBundleClass($bundle);
            $namespace = substr($bundle, 0, strrpos($bundle, '\\')) . '\\' .
                self::DOCUMENT_DIR . '\\' . $document;
        }

        return $namespace;
    }

    /**
     * Returns bundle class namespace else throws an exception.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws \LogicException
     */
    public function getBundleClass($name)
    {
        if (array_key_exists($name, $this->bundles)) {
            return $this->bundles[$name];
        }

        throw new \LogicException(sprintf('Bundle \'%s\' does not exist.', $name));
    }

    /**
     * Returns bundle document paths.
     *
     * @param string $bundle
     *
     * @return array
     */
    public function getBundleDocumentPaths($bundle)
    {
        $bundleReflection = new \ReflectionClass($this->getBundleClass($bundle));

        $path = dirname($bundleReflection->getFileName()) .
            DIRECTORY_SEPARATOR .
            self::DOCUMENT_DIR .
            DIRECTORY_SEPARATOR .
            '*.php';

        return $this->rglob($path);
    }

    /**
     * Recusive glob
     * @param $pattern
     * @param int $flags
     * @return array
     */
    private function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}
