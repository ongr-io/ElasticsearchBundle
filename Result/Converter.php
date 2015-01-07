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

use Doctrine\Common\Util\Inflector;
use ONGR\ElasticsearchBundle\Document\DocumentInterface;

/**
 * This class converts array to document object.
 */
class Converter
{
    /**
     * @var array
     */
    private $typesMapping;

    /**
     * @var array
     */
    private $bundlesMapping;

    /**
     * Constructor.
     *
     * @param array $typesMapping
     * @param array $bundlesMapping
     */
    public function __construct($typesMapping, $bundlesMapping)
    {
        $this->typesMapping = $typesMapping;
        $this->bundlesMapping = $bundlesMapping;
    }

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return DocumentInterface Document
     *
     * @throws \LogicException
     */
    public function convertToDocument($rawData)
    {
        if (!isset($this->typesMapping[$rawData['_type']])) {
            throw new \LogicException("Got document of unknown type '{$rawData['_type']}'.");
        }

        $metadata = $this->bundlesMapping[$this->typesMapping[$rawData['_type']]];
        $data = isset($rawData['_source']) ? $rawData['_source'] : array_map('reset', $rawData['fields']);

        /** @var DocumentInterface $object */
        $object = $this->assignArrayToObject($data, new $metadata['proxyNamespace'](), $metadata['aliases']);

        isset($rawData['_id']) && $object->setId($rawData['_id']);
        isset($rawData['_score']) && $object->setScore($rawData['_score']);
        isset($rawData['highlight']) && $object->setHighlight(new DocumentHighlight($rawData['highlight']));
        isset($rawData['fields']['_parent']) && $object->setParent($rawData['fields']['_parent']);
        isset($rawData['fields']['_ttl']) && $object->setTtl($rawData['fields']['_ttl']);

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
     *
     * @throws \LogicException
     */
    public function assignArrayToObject(array $array, $object, array $aliases)
    {
        foreach ($array as $name => $value) {
            if (!array_key_exists($name, $aliases)) {
                throw new \LogicException("Undefined property '{$name}'.");
            }

            if ($aliases[$name]['type'] === 'date') {
                $value = \DateTime::createFromFormat(\DateTime::ISO8601, $value);
            }

            if (array_key_exists('aliases', $aliases[$name])) {
                if ($aliases[$name]['multiple']) {
                    $value = new ObjectIterator($this, $value, $aliases[$name]);
                } else {
                    $value = $this->assignArrayToObject(
                        $value,
                        new $aliases[$name]['proxyNamespace'](),
                        $aliases[$name]['aliases']
                    );
                }
            }

            $this->setPropertyValue($object, $aliases[$name]['propertyName'], $value);
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
     *
     * @throws \RuntimeException Property could not be accessed.
     */
    public function convertToArray($object, $aliases = [])
    {
        if (empty($aliases)) {
            $aliases = $this->getAlias($object);
        }

        $array = [];
        // Special fields.
        if ($object instanceof DocumentInterface) {
            if ($object->getId()) {
                $array['_id'] = $object->getId();
            }

            if ($object->hasParent()) {
                $array['_parent'] = $object->getParent();
            }

            if ($object->getTtl()) {
                $array['_ttl'] = $object->getTtl();
            }
        }

        // Variable $name defined in client.
        foreach ($aliases as $name => $alias) {
            try {
                $value = $this->getPropertyValue($object, $alias['propertyName']);
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "Cannot access {$alias['propertyName']} property. "
                    . 'Please define a setter or create document with Manager::createDocument.'
                );
            }

            if (isset($value)) {
                if (array_key_exists('aliases', $alias)) {
                    $new = null;
                    if ($alias['multiple']) {
                        $this->isTraversable($value);
                        foreach ($value as $item) {
                            $this->checkVariableType($item, [$alias['namespace'], $alias['proxyNamespace']]);
                            $new[] = $this->convertToArray($item, $alias['aliases']);
                        }
                    } else {
                        $this->checkVariableType($value, [$alias['namespace'], $alias['proxyNamespace']]);
                        $new = $this->convertToArray($value, $alias['aliases']);
                    }
                    $value = $new;
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format(\DateTime::ISO8601);
                }

                $array[$name] = $value;
            }
        }

        return $array;
    }

    /**
     * Returns property value from a document.
     *
     * @param DocumentInterface $document
     * @param string            $propertyName
     *
     * @return mixed
     */
    private function getPropertyValue($document, $propertyName)
    {
        $method = 'get' . ucfirst(Inflector::classify($propertyName));

        if (method_exists($document, $method)) {
            return $document->{$method}();
        }

        return $document->{$propertyName};
    }

    /**
     * Sets property value to a document.
     *
     * @param DocumentInterface $document
     * @param string            $propertyName
     * @param mixed             $value
     */
    private function setPropertyValue($document, $propertyName, $value)
    {
        $method = 'set' . ucfirst(Inflector::classify($propertyName));

        if (method_exists($document, $method)) {
            $document->{$method}($value);
        } else {
            $document->{$propertyName} = $value;
        }
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
     * @return array|null
     *
     * @throws \DomainException
     */
    private function getAlias($document)
    {
        $class = get_class($document);

        foreach ($this->bundlesMapping as $repository) {
            if (in_array($class, [$repository['namespace'], $repository['proxyNamespace']])) {
                return $repository['aliases'];
            }
        }

        throw new \DomainException("Aliases could not be found for {$class} document.");
    }
}
