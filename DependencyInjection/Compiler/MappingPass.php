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
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MappingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $parser = $container->get(DocumentParser::class);
        $converterDefinition = (new Definition(Converter::class))->setAutowired(true);
        $eventDispatcherDefinition = (new Definition(EventDispatcher::class))->setAutowired(true);
        $parserDefinition = new Definition(DocumentParser::class);

        $indexes = [];
        foreach (
            $this->getNamespaces(
                $container->getParameter('kernel.project_dir')
                .$container->getParameter(Configuration::ONGR_SOURCE_DIR)
            ) as $namespace) {

            $indexMapping = $parser->getIndexMetadata($namespace);
            $indexAlias = $parser->getIndexAliasName($namespace);
            if (!empty($indexMapping)) {
                $indexServiceDefinition = new Definition(IndexService::class, [
                    $namespace,
                    $converterDefinition,
                    $parserDefinition,
                    $eventDispatcherDefinition
                ]);
                $indexServiceDefinition->setPublic(true);

                $container->setDefinition($namespace, $indexServiceDefinition);
                $container->setAlias($indexAlias, $namespace);
                $indexes[] = $namespace;
            }
        }

        $container->setParameter('ongr.es.indexes', $indexes);
    }

    private function getNamespaces($directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $documents = [];

        foreach ($files as $file => $v) {
            $documents[] = $this->getFullNamespace($file) . '\\' . $this->getClassname($file);
        }

        return $documents;
    }

    private function getFullNamespace($filename) {
        $lines = preg_grep('/^namespace /', file($filename));
        $namespaceLine = array_shift($lines);
        $match = array();
        preg_match('/^namespace (.*);$/', $namespaceLine, $match);
        $fullNamespace = array_pop($match);

        return $fullNamespace;
    }

    private function getClassname($filename) {
        $directoriesAndFilename = explode('/', $filename);
        $filename = array_pop($directoriesAndFilename);
        $nameAndExtension = explode('.', $filename);
        $className = array_shift($nameAndExtension);

        return $className;
    }
}
