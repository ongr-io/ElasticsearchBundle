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
    private $documentDir;

    /**
     * Constructor.
     *
     * @param array $bundles Parameter kernel.bundles from service container.
     */
    public function __construct(array $bundles)
    {
        $this->documentDir = 'Document';
        $this->bundles = $bundles;
    }

    /**
     * @return string
     */
    public function getDocumentDir()
    {
        return $this->documentDir;
    }

    /**
     * @param string $documentDir
     */
    public function setDocumentDir($documentDir)
    {
        $this->documentDir = $documentDir;
    }

    /**
     * Formats namespace from short syntax.
     *
     * @param string $namespace
     * @param string $documentsDirectory Directory name where documents are stored in the bundle.
     *
     * @return string
     */
    public function getNamespace($namespace, $documentsDirectory = null)
    {
        if (!$documentsDirectory) {
            $documentsDirectory = $this->documentDir;
        }

        if (strpos($namespace, ':') !== false) {
            list($bundle, $document) = explode(':', $namespace);
            $bundle = $this->getBundleClass($bundle);

            // If bundle has a sub-namespace it needs to be replaced
            if (strpos($documentsDirectory, '\\')) {
                $bundleSubNamespace = substr(
                    $bundle,
                    $start = strpos($bundle, '\\') + 1,
                    strrpos($bundle, '\\') - $start + 1
                );

                $documentsDirectory = str_replace(
                    $bundleSubNamespace,
                    '',
                    $documentsDirectory
                );
            }

            $namespace = substr($bundle, 0, strrpos($bundle, '\\')) . '\\' .
                $documentsDirectory . '\\' . $document;
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
     * @param string $bundle Bundle name. E.g. AppBundle
     * @param string $documentsDirectory Directory name where documents are stored in the bundle.
     *
     * @return array
     */
    public function getBundleDocumentClasses($bundle, $documentsDirectory = null)
    {
        if (!$documentsDirectory) {
            $documentsDirectory = $this->documentDir;
        }

        $bundleReflection = new \ReflectionClass($this->getBundleClass($bundle));

        $documentsDirectory = DIRECTORY_SEPARATOR . str_replace('\\', '/', $documentsDirectory) . DIRECTORY_SEPARATOR;
        $directory = dirname($bundleReflection->getFileName()) . $documentsDirectory;

        if (!is_dir($directory)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $documents = [];

        foreach ($files as $file => $v) {
            $documents[] = str_replace(
                DIRECTORY_SEPARATOR,
                '\\',
                substr(strstr($file, $documentsDirectory), strlen($documentsDirectory), -4)
            );
        }

        return $documents;
    }
}
