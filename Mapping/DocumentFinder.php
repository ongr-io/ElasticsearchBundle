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
     * Returns a list of bundle document classes.
     *
     * Example output:
     *
     *     [
     *         'Category',
     *         'Product',
     *         'SubDir\SomeObject'
     *     ]
     *
     * @param string $bundle
     *
     * @return array
     */
    public function getBundleDocumentClasses($bundle)
    {
        $bundleReflection = new \ReflectionClass($this->getBundleClass($bundle));

        $documentDirectory = DIRECTORY_SEPARATOR . self::DOCUMENT_DIR . DIRECTORY_SEPARATOR;
        $directory = dirname($bundleReflection->getFileName()) . $documentDirectory;

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $documents = [];

        foreach ($files as $file => $v) {
            $documents[] = str_replace(
                DIRECTORY_SEPARATOR,
                '\\',
                substr(strstr($file, $documentDirectory), strlen($documentDirectory), -4)
            );
        }

        return $documents;
    }
}
