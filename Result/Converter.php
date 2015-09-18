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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * @var PropertyAccessor
     */
    private $propertyAccessor;

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
     * @param array $mappings Manager mappings to tell converter which types belongs to which objects.
     *
     * @return DocumentInterface
     *
     * @throws \LogicException
     */
    public function convertToDocument($rawData, array $mappings = [])
    {
        $types = $this->metadataCollector->getMappings($mappings);

        if (isset($types[$rawData['_type']])) {
            $metadata = $types[$rawData['_type']];
        } else {
            throw new \LogicException("Got document of unknown type '{$rawData['_type']}'.");
        }

        $data = isset($rawData['_source']) ? $rawData['_source'] : array_map('reset', $rawData['fields']);

        /** @var DocumentInterface $object */
        $object = $this->assignArrayToObject($data, new $metadata['namespace'](), $metadata['aliases']);

        $this->setObjectFields($object, $rawData, ['_id', '_score', 'fields _parent', 'fields _ttl']);

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
            if (!array_key_exists($name, $aliases) || $value === null) {
                $object->{$name} = $value;
                continue;
            }

            if ($aliases[$name]['type'] === 'date') {
                $newValue = \DateTime::createFromFormat(
                    isset($aliases[$name]['format']) ? $aliases[$name]['format'] : \DateTime::ISO8601,
                    $value
                );
                
                $value = $newValue === false ? $value : $newValue;
            }

            if (array_key_exists('aliases', $aliases[$name])) {
                if ($aliases[$name]['multiple']) {
                    $value = new ObjectIterator($this, $value, $aliases[$name]);
                } else {
                    $value = $this->assignArrayToObject(
                        $value,
                        new $aliases[$name]['namespace'](),
                        $aliases[$name]['aliases']
                    );
                }
            }

            $this->getPropertyAccessor()->setValue($object, $aliases[$name]['propertyName'], $value);
        }

        return $object;
    }

    /**
     * Converts object to an array.
     *
     * @param DocumentInterface $object
     * @param array             $aliases
     *
     * @return array
     */
    public function convertToArray($object, $aliases = [])
    {
        if (empty($aliases)) {
            $aliases = $this->getAlias($object);
        }

        $array = [];
        // Special fields.
        if ($object instanceof DocumentInterface) {
            $this->setArrayFields($array, $object, ['_id', '_parent', '_ttl']);
        }

        // Variable $name defined in client.
        foreach ($aliases as $name => $alias) {
            $value = $this->getPropertyAccessor()->getValue($object, $alias['propertyName']);

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
     * Sets fields into object from raw response.
     *
     * @param object $object      Object to set values to.
     * @param array  $rawResponse Array to take values from.
     * @param array  $fields      Values to take.
     */
    private function setObjectFields($object, $rawResponse, $fields = [])
    {
        foreach ($fields as $field) {
            $path = $this->getPropertyPathToAccess($field);
            $value = $this->getPropertyAccessor()->getValue($rawResponse, $path);

            if ($value !== null) {
                $this->getPropertyAccessor()->setValue($object, $this->getPropertyToAccess($field), $value);
            }
        }
    }

    /**
     * Sets fields into array from object.
     *
     * @param array  $array  To set values to.
     * @param object $object Take values from.
     * @param array  $fields Fields to set.
     */
    private function setArrayFields(&$array, $object, $fields = [])
    {
        foreach ($fields as $field) {
            $value = $this->getPropertyAccessor()->getValue($object, $this->getPropertyToAccess($field));

            if ($value !== null) {
                $this
                    ->getPropertyAccessor()
                    ->setValue($array, $this->getPropertyPathToAccess($field), $value);
            }
        }
    }

    /**
     * Returns property to access for object used by property accessor.
     *
     * @param string $field
     *
     * @return string
     */
    private function getPropertyToAccess($field)
    {
        $deep = strpos($field, ' ');
        if ($deep !== false) {
            $field = substr($field, $deep + 1);
        }

        return $field;
    }

    /**
     * Returns property to access for array used by property accessor.
     *
     * @param string $field
     *
     * @return string
     */
    private function getPropertyPathToAccess($field)
    {
        return '[' . str_replace(' ', '][', $field) . ']';
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
        $documentMapping = $this->collector->getDocumentMapping($document);
        if (is_array($documentMapping) && isset($documentMapping['aliases'])) {
            return $documentMapping['aliases'];
        }

        throw new \DomainException("Aliases could not be found for {$class} document.");
    }

    /**
     * Returns property accessor instance.
     *
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->enableMagicCall()
                ->getPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
