<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

use ONGR\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Service\Repository;

/**
 * This class converts array to document object.
 */
class Converter
{
    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * Constructor.
     *
     * @param MetadataCollector $metadataCollector
     */
    public function __construct($metadataCollector)
    {
        $this->metadataCollector = $metadataCollector;
    }

    /**
     * Converts raw array to document.
     *
     * @param array      $rawData
     * @param Repository $repository
     *
     * @return DocumentInterface
     *
     * @throws \LogicException
     */
    public function convertToDocument($rawData, Repository $repository)
    {
        $types = $this->metadataCollector->getMappings($repository->getManager()->getConfig()['mappings']);

        if (isset($types[$rawData['_type']])) {
            $metadata = $types[$rawData['_type']];
        } else {
            throw new \LogicException("Got document of unknown type '{$rawData['_type']}'.");
        }

        switch (true) {
            case isset($rawData['_source']):
                $rawData = array_merge($rawData, $rawData['_source']);
                break;
            case isset($rawData['fields']):
                $rawData = array_merge($rawData, $rawData['fields']);
                break;
            default:
                // Do nothing.
                break;
        }

        /** @var DocumentInterface $object */
        $object = $this->assignArrayToObject($rawData, new $metadata['namespace'](), $metadata['aliases']);

        return $object;
    }

    /**
     * Assigns all properties to object.
     *
     * @param array            $array
     * @param \ReflectionClass $object
     * @param array            $aliases
     *
     * @return object
     */
    public function assignArrayToObject(array $array, $object, array $aliases)
    {
        foreach ($array as $name => $value) {
            if (!isset($aliases[$name]['type'])) {
                continue;
            }
            switch ($aliases[$name]['type']) {
                case 'date':
                    $value = \DateTime::createFromFormat(
                        isset($aliases[$name]['format']) ? $aliases[$name]['format'] : \DateTime::ISO8601,
                        $value
                    );
                    break;
                case 'object':
                case 'nested':
                    if ($aliases[$name]['multiple']) {
                        $value = new ObjectIterator($this, $value, $aliases[$name]);
                    } else {
                        if (!isset($value)) {
                            $value = new ObjectIterator($this, $value, $aliases[$name]);
                            break;
                        }
                        $value = $this->assignArrayToObject(
                            $value,
                            new $aliases[$name]['namespace'](),
                            $aliases[$name]['aliases']
                        );
                    }
                    break;
                default:
                    // Do nothing here. Default cas is required by our code style standard.
                    break;
            }

            if ($aliases[$name]['propertyType'] == 'private') {
                $object->{$aliases[$name]['methods']['setter']}($value);
            } else {
                $object->{$aliases[$name]['propertyName']} = $value;
            }
        }

        return $object;
    }

    /**
     * Converts object to an array.
     *
     * @param mixed $object
     * @param array $aliases
     *
     * @return array
     */
    public function convertToArray($object, $aliases = [])
    {
        if (empty($aliases)) {
            $aliases = $this->getAlias($object);
        }

        $array = [];

        // Variable $name defined in client.
        foreach ($aliases as $name => $alias) {
            if ($aliases[$name]['propertyType'] == 'private') {
                $value = $object->{$aliases[$name]['methods']['getter']}();
            } else {
                $value = $object->{$aliases[$name]['propertyName']};
            }

            if (isset($value)) {
                if (array_key_exists('aliases', $alias)) {
                    $new = [];
                    if ($alias['multiple']) {
                        $this->isTraversable($value);
                        foreach ($value as $item) {
                            $this->checkVariableType($item, [$alias['namespace']]);
                            $new[] = $this->convertToArray($item, $alias['aliases']);
                        }
                    } else {
                        $this->checkVariableType($value, [$alias['namespace']]);
                        $new = $this->convertToArray($value, $alias['aliases']);
                    }
                    $value = $new;
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format(isset($alias['format']) ? $alias['format'] : \DateTime::ISO8601);
                }

                $array[$name] = $value;
            }
        }

        return $array;
    }

    /**
     * Check if class matches the expected one.
     *
     * @param object $object
     * @param array  $expectedClasses
     *
     * @throws \InvalidArgumentException
     */
    private function checkVariableType($object, array $expectedClasses)
    {
        if (!is_object($object)) {
            $msg = 'Expected variable of type object, got ' . gettype($object) . ". (field isn't multiple)";
            throw new \InvalidArgumentException($msg);
        }

        $class = get_class($object);
        if (!in_array($class, $expectedClasses)) {
            throw new \InvalidArgumentException("Expected object of type {$expectedClasses[0]}, got {$class}.");
        }
    }

    /**
     * Check if object is traversable, throw exception otherwise.
     *
     * @param mixed $value
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    private function isTraversable($value)
    {
        if (!(is_array($value) || (is_object($value) && $value instanceof \Traversable))) {
            throw new \InvalidArgumentException("Variable isn't traversable, although field is set to multiple.");
        }

        return true;
    }

    /**
     * Returns aliases for certain document.
     *
     * @param DocumentInterface $document
     *
     * @return array
     *
     * @throws \DomainException
     */
    private function getAlias($document)
    {
        $class = get_class($document);
        $documentMapping = $this->metadataCollector->getDocumentMapping($document);
        if (is_array($documentMapping) && isset($documentMapping['aliases'])) {
            return $documentMapping['aliases'];
        }

        throw new \DomainException("Aliases could not be found for {$class} document.");
    }
}
