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

use ONGR\ElasticsearchBundle\Annotation\Index;
use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Mapping\IndexSettings;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MappingPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $indexes = [];

    /**
     * @var string
     */
    private $defaultIndex = null;

    public function process(ContainerBuilder $container)
    {
        $kernelDir = $container->getParameter('kernel.project_dir');

        foreach ($container->getParameter(Configuration::ONGR_SOURCE_DIR) as $dir) {
            $this->handleDirectoryMapping($container, $kernelDir . $dir);
        }

        $container->setParameter(Configuration::ONGR_INDEXES, $this->indexes);
        $container->setParameter(
            Configuration::ONGR_DEFAULT_INDEX,
            $this->defaultIndex ?? current(array_keys($this->indexes))
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string $dir
     *
     * @throws \ReflectionException
     */
    private function handleDirectoryMapping(ContainerBuilder $container, string $dir): void
    {
        /** @var DocumentParser $parser */
        $parser = $container->get(DocumentParser::class);
        $indexesOverride = $container->getParameter(Configuration::ONGR_INDEXES_OVERRIDE);
        $converterDefinition = $container->getDefinition(Converter::class);

        foreach ($this->getNamespaces($dir) as $namespace) {
            $class = new \ReflectionClass($namespace);

            if (isset($indexesOverride[$namespace]['alias']) && $indexesOverride[$namespace]['alias']) {
                $indexAlias = $indexesOverride[$namespace]['alias'];
            } else {
                $indexAlias = $parser->getIndexAliasName($class);
            }

            /** @var Index $document */
            $document = $parser->getIndexAnnotation($class);
            $indexMetadata = $parser->getIndexMetadata($class);

            if (!empty($indexMetadata)) {
                $indexMetadata['settings'] = array_filter(
                    array_replace_recursive(
                        $indexMetadata['settings'] ?? [],
                        [
                            'number_of_replicas' => $document->numberOfReplicas,
                            'number_of_shards' => $document->numberOfShards,
                        ],
                        $indexesOverride[$namespace]['settings'] ?? []
                    ),
                    function ($value) {
                        if (0 === $value) {
                            return true;
                        }

                        return (bool)$value;
                    }
                );

                $indexSettings = new Definition(
                    IndexSettings::class,
                    [
                        $namespace,
                        $indexAlias,
                        $indexAlias,
                        $indexMetadata,
                        $indexesOverride[$namespace]['hosts'] ?? $document->hosts,
                        $indexesOverride[$namespace]['default'] ?? $document->default,
                        $indexesOverride[$namespace]['type'] ?? $document->typeName
                    ]
                );

                $indexServiceDefinition = new Definition(IndexService::class, [
                    $namespace,
                    $converterDefinition,
                    $container->getDefinition('event_dispatcher'),
                    $indexSettings,
                    $container->getParameter(Configuration::ONGR_PROFILER_CONFIG)
                        ? $container->getDefinition('ongr.esb.tracer') : null
                ]);
                $indexServiceDefinition->setPublic(true);
                $converterDefinition->addMethodCall(
                    'addClassMetadata',
                    [
                        $namespace,
                        $parser->getPropertyMetadata($class)
                    ]
                );

                $container->setDefinition($namespace, $indexServiceDefinition);
                $this->indexes[$indexAlias] = $namespace;
                $isCurrentIndexDefault = $parser->isDefaultIndex($class);
                if ($this->defaultIndex && $isCurrentIndexDefault) {
                    throw new \RuntimeException(
                        sprintf(
                            'Only one index can be set as default. We found 2 indexes as default ones `%s` and `%s`',
                            $this->defaultIndex,
                            $indexAlias
                        )
                    );
                }

                if ($isCurrentIndexDefault) {
                    $this->defaultIndex = $indexAlias;
                }
            }
        }
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

    private function getFullNamespace($filename)
    {
        $lines = preg_grep('/^namespace /', file($filename));
        $namespaceLine = array_shift($lines);
        $match = array();
        preg_match('/^namespace (.*);$/', $namespaceLine, $match);
        $fullNamespace = array_pop($match);

        return $fullNamespace;
    }

    private function getClassname($filename)
    {
        $directoriesAndFilename = explode('/', $filename);
        $filename = array_pop($directoriesAndFilename);
        $nameAndExtension = explode('.', $filename);
        $className = array_shift($nameAndExtension);

        return $className;
    }
}
