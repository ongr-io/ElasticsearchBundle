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

use Doctrine\Common\Collections\Collection;
use ONGR\ElasticsearchBundle\Annotation\Nested;
use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Service\Manager;

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
     * @param array $rawData
     * @param Manager $manager
     *
     * @return object
     *
     * @throws \LogicException
     */
    public function convertToDocument($rawData, Manager $manager)
    {
        $types = $this->metadataCollector->getMappings($manager->getConfig()['mappings']);

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

        $object = $this->assignArrayToObject($rawData, new $metadata['namespace'](), $metadata['aliases']);

        return $object;
    }

    /**
     * Assigns all properties to object.
     *
     * @param array  $array
     * @param object $object
     * @param array  $aliases
     *
     * @return object
     */
    public function assignArrayToObject(array $array, $object, array $aliases)
    {
        foreach ($array as $name => $value) {
            if (!isset($aliases[$name])) {
                continue;
            }

            if (isset($aliases[$name]['type'])) {
                switch ($aliases[$name]['type']) {
                    case 'date':
                        if (is_null($value) || (is_object($value) && $value instanceof \DateTimeInterface)) {
                            continue 2;
                        }
                        if (is_numeric($value) && (int)$value == $value) {
                            $time = $value;
                            $value = new \DateTime();
                            $value->setTimestamp($time);
                        } else {
                            $value = new \DateTime($value);
                        }
                        break;
                    case ObjectType::NAME:
                    case Nested::NAME:
                        if ($aliases[$name]['multiple']) {
                            $value = new ObjectIterator($this, $value, $aliases[$name]);
                        } else {
                            if (!isset($value)) {
                                break;
                            }
                            $value = $this->assignArrayToObject(
                                $value,
                                new $aliases[$name]['namespace'](),
                                $aliases[$name]['aliases']
                            );
                        }
                        break;
                    case 'boolean':
                        if (!is_bool($value)) {
                            $value = (bool)$value;
                        }
                        break;
                    default:
                        // Do nothing here. Default cas is required by our code style standard.
                        break;
                }
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
     * @param array $fields
     *
     * @return array
     */
    public function convertToArray($object, $aliases = [], $fields = [])
    {
        if (empty($aliases)) {
            $aliases = $this->getAlias($object);
            if (count($fields) > 0) {
                $aliases = array_intersect_key($aliases, array_flip($fields));
            }
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
                        $this->isCollection($aliases[$name]['propertyName'], $value);
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

                if (isset($alias['type'])) {
                    switch ($alias['type']) {
                        case 'float':
                            if (is_array($value)) {
                                foreach ($value as $key => $item) {
                                    $value[$key] = (float)$item;
                                }
                            } else {
                                $value = (float)$value;
                            }
                            break;
                        case 'integer':
                            if (is_array($value)) {
                                foreach ($value as $key => $item) {
                                    $value[$key] = (int)$item;
                                }
                            } else {
                                $value = (int)$value;
                            }
                            break;
                        default:
                            break;
                    }
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
     * @param array $expectedClasses
     *
     * @throws \InvalidArgumentException
     */
    private function checkVariableType($object, array $expectedClasses)
    {
        if (!is_object($object)) {
            $msg = 'Expected variable of type object, got ' . gettype($object) . ". (field isn't multiple)";
            throw new \InvalidArgumentException($msg);
        }

        $classes = class_parents($object);
        $classes[] = $class = get_class($object);
        if (empty(array_intersect($classes, $expectedClasses))) {
            throw new \InvalidArgumentException("Expected object of type {$expectedClasses[0]}, got {$class}.");
        }
    }

    /**
     * Check if value is instance of Collection.
     *
     * @param string $property
     * @param mixed $value
     *
     * @throws \InvalidArgumentException
     */
    private function isCollection($property, $value)
    {
        if (!$value instanceof Collection) {
            $got = is_object($value) ? get_class($value) : gettype($value);

            throw new \InvalidArgumentException(
                sprintf('Value of "%s" property must be an instance of Collection, got %s.', $property, $got)
            );
        }
    }

    /**
     * Returns aliases for certain document.
     *
     * @param object $document
     *
     * @return array
     */
    private function getAlias($document)
    {
        $class = get_class($document);
        $documentMapping = $this->metadataCollector->getMapping($class);

        return $documentMapping['aliases'];
    }
}
