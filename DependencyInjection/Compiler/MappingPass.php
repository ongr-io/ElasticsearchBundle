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
use ONGR\ElasticsearchBundle\Exception\DocumentIndexParserException;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Mapping\IndexSettings;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
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
        $parser = $container->get(DocumentParser::class);

        $indexClasses = [];
        $indexSettingsArray = [];
        foreach ($container->getParameter(Configuration::ONGR_SOURCE_DIR) as $dir) {
            foreach ($this->getNamespaces($kernelDir . $dir) as $namespace) {
                $indexClasses[$namespace] = $namespace;
            }
        }

        $overwrittenClasses = [];
        $indexOverrides = $container->getParameter(Configuration::ONGR_INDEXES_OVERRIDE);

        foreach ($indexOverrides as $name => $indexOverride) {
            $class = isset($indexOverride['class']) ? $indexOverride['class'] : $name;

            if (!isset($indexClasses[$class])) {
                throw new \RuntimeException(
                    sprintf(
                        'Document `%s` defined in ongr_elasticsearch.indexes config could not been found',
                        $class,
                    )
                );
            }

            $indexSettings = $this->parseIndexSettingsFromClass($parser, $class);

            if ($class !== $name) {
                $indexSettings->setIndexName('ongr.es.index.'.$name);
            }

            if (isset($indexOverride['alias'])) {
                $indexSettings->setAlias($indexOverride['alias']);
            }

            if (isset($indexOverride['settings'])) {
                $indexSettings->setIndexMetadata($indexOverride['settings']);
            }

            if (isset($indexOverride['hosts'])) {
                $indexSettings->setHosts($indexOverride['hosts']);
            }

            if (isset($indexOverride['default'])) {
                $indexSettings->setDefaultIndex($indexOverride['default']);
            }

            $indexSettingsArray[$name] = $indexSettings;
            $overwrittenClasses[$class] = $class;
        }

        foreach (array_diff($indexClasses, $overwrittenClasses) as $indexClass) {
            try {
                $indexSettingsArray[$indexClass] = $this->parseIndexSettingsFromClass($parser, $indexClass);
            } catch (DocumentIndexParserException $e) {}
        }

        foreach($indexSettingsArray as $indexSettings) {
            $this->createIndex($container, $indexSettings);
        }

        $container->setParameter(Configuration::ONGR_INDEXES, $this->indexes);
        $container->setParameter(
            Configuration::ONGR_DEFAULT_INDEX,
            $this->defaultIndex ?? current(array_keys($this->indexes))
        );
    }

    private function parseIndexSettingsFromClass(DocumentParser $parser, string $className) : IndexSettings
    {
        $class = new \ReflectionClass($className);

        /** @var Index $document */
        $document = $parser->getIndexAnnotation($class);

        if ($document === null) {
            throw new DocumentIndexParserException();
        }

        $indexSettings = new IndexSettings(
            $className,
            $className,
            $parser->getIndexAliasName($class),
            $parser->getIndexMetadata($class),
            $parser->getPropertyMetadata($class),
            $document->hosts,
            $parser->isDefaultIndex($class)
        );

        $indexSettings->setIndexMetadata(['settings' => [
            'number_of_replicas' => $document->numberOfReplicas,
            'number_of_shards' => $document->numberOfShards,
        ]]);

        return $indexSettings;
    }

    private function createIndex(Container $container, IndexSettings $indexSettings) {
        $converterDefinition = $container->getDefinition(Converter::class);

        $indexSettingsDefinition = new Definition(
            IndexSettings::class,
            [
                $indexSettings->getNamespace(),
                $indexSettings->getAlias(),
                $indexSettings->getAlias(),
                $indexSettings->getIndexMetadata(),
                $indexSettings->getPropertyMetadata(),
                $indexSettings->getHosts(),
                $indexSettings->isDefaultIndex(),
            ]
        );

        $indexServiceDefinition = new Definition(IndexService::class, [
            $indexSettings->getNamespace(),
            $converterDefinition,
            $container->getDefinition('event_dispatcher'),
            $indexSettingsDefinition,
            $container->getParameter(Configuration::ONGR_PROFILER_CONFIG)
                ? $container->getDefinition('ongr.esb.tracer') : null
        ]);

        $indexServiceDefinition->setPublic(true);
        $converterDefinition->addMethodCall(
            'addClassMetadata',
            [
                $indexSettings->getNamespace(),
                $indexSettings->getPropertyMetadata()
            ]
        );

        $container->setDefinition($indexSettings->getIndexName(), $indexServiceDefinition);
        $this->indexes[$indexSettings->getAlias()] = $indexSettings->getIndexName();
        $isCurrentIndexDefault = $indexSettings->isDefaultIndex();
        if ($this->defaultIndex && $isCurrentIndexDefault) {
            throw new \RuntimeException(
                sprintf(
                    'Only one index can be set as default. We found 2 indexes as default ones `%s` and `%s`',
                    $this->defaultIndex,
                    $indexSettings->getAlias()
                )
            );
        }

        if ($isCurrentIndexDefault) {
            $this->defaultIndex = $indexSettings->getAlias();
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
