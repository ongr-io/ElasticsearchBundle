<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use ONGR\ElasticsearchBundle\Generator\DocumentGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generate Service
 */
class GenerateService
{
    /**
     * @var DocumentGenerator
     */
    private $generator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor
     *
     * @param DocumentGenerator $generator
     * @param Filesystem        $filesystem
     */
    public function __construct(DocumentGenerator $generator, Filesystem $filesystem)
    {
        $this->generator = $generator;
        $this->filesystem = $filesystem;
    }

    /**
     * Generates document class
     *
     * @param BundleInterface $bundle
     * @param string          $document
     * @param string          $type
     * @param array           $properties
     */
    public function generate(
        BundleInterface $bundle,
        $document,
        $type,
        array $properties
    ) {
        $documentPath = $bundle->getPath() . '/Document/' . str_replace('\\', '/', $document) . '.php';
        $class = new ClassMetadataInfo($bundle->getNamespace() . '\\Document\\' . $document);

        foreach ($properties as $property) {
            $class->mapField($property);
        }

        $documentCode = $this->generator
            ->setDocumentType($type)
            ->generateEntityClass($class);

        $this->filesystem->mkdir(dirname($documentPath));
        file_put_contents($documentPath, $documentCode);
    }
}
