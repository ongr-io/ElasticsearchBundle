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
    private $documentDir = 'Document';

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
                str_replace('/', '\\', $this->getDocumentDir()) . '\\' . $document;
        }

        return $namespace;
    }

    /**
     * Returns bundle class namesapce else throws an exception.
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
     * Document directory in bundle to load documents from.
     *
     * @param string $documentDir
     */
    public function setDocumentDir($documentDir)
    {
        $this->documentDir = $documentDir;
    }

    /**
     * Returns directory name in which documents should be put.
     *
     * @return string
     */
    public function getDocumentDir()
    {
        return $this->documentDir;
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

        return glob(
            dirname($bundleReflection->getFileName()) .
            DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->getDocumentDir()) .
            DIRECTORY_SEPARATOR . '*.php'
        );
    }
}
