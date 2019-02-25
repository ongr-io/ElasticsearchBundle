<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DependencyInjection\Compiler;

use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * Compiles elastic search data.
 */
class MappingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $analysis = $container->getParameter(Configuration::ONGR_ANALYSIS_CONFIG);

        $collector = $container->get('es.metadata_collector');

        $kernelProjectDir = $container->getParameter('kernel.project_dir');

        foreach ($this->getNamespaces($kernelProjectDir . '/src') as $namespace) {
            $indexMapping = $collector->getMapping($namespace);
            $definition = new Definition(IndexService::class, [

            ]);
        }
    }

    private function getNamespaces($directory): array
    {
        $documentsDirectory = DIRECTORY_SEPARATOR . str_replace('\\', '/', $directory) . DIRECTORY_SEPARATOR;

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
