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

use ONGR\ElasticsearchBundle\Result\ObjectIterator;

/**
 * This class converts array to document object.
 */
class Converter
{
    private $propertyMetadata = [];

    public function addClassMetadata(string $class, array $metadata): void
    {
        $this->propertyMetadata[$class] = $metadata;
    }

    public function convertArrayToDocument(string $namespace, array $raw)
    {
        if (!isset($this->propertyMetadata[$namespace])) {
            throw new \Exception("Cannot convert array to object of class `$class`.");
        }

        return $this->denormalize($raw, $namespace);
    }

    public function convertDocumentToArray($document): array
    {
        $class = get_class($document);

        if (!isset($this->propertyMetadata[$class])) {
            throw new \Exception("Cannot convert object of class `$class` to array.");
        }

        return $this->normalize($document);
    }

    protected function normalize($document, $metadata = null)
    {
        $metadata = $metadata ?? $this->propertyMetadata[get_class($document)];
        $result = [];

        foreach ($metadata as $field => $fieldMeta) {
            $getter = $fieldMeta['getter'];
            $value = $fieldMeta['public'] ? $document->{$fieldMeta['name']} : $document->$getter();

            if ($fieldMeta['embeded']) {
                if (is_iterable($value)) {
                    foreach ($value as $item) {
                        $result[$field][] = $this->normalize($item, $fieldMeta['sub_properties']);
                    }
                } else {
                    $result[$field] = $this->normalize($value, $fieldMeta['sub_properties']);
                }
            } else {
                if ($value instanceof \DateTime) {
                    $value = $value->format(\DateTimeInterface::ISO8601);
                }
                $result[$field] = $value;
            }
        }

        return $result;
    }

    protected function denormalize(array $raw, string $namespace)
    {
        $metadata = $this->propertyMetadata[$namespace];
        $object = new $namespace();

        foreach ($raw as $field => $value) {
            $fieldMeta = $metadata[$field];
            $setter = $fieldMeta['setter'];

            if ($fieldMeta['embeded']) {
                $this->addClassMetadata($fieldMeta['class'], $fieldMeta['sub_properties']);
                $iterator = new ObjectIterator($fieldMeta['class'], $value, $this);

                if ($fieldMeta['public']) {
                    $object->{$fieldMeta['name']} = $iterator;
                } else {
                    $object->$setter($iterator);
                }
            } else {
                if ($fieldMeta['type'] == 'date') {
                    $value = \DateTime::createFromFormat(\DateTimeInterface::ISO8601, $value);
                }
                if ($fieldMeta['public']) {
                    $object->{$fieldMeta['name']} = $value;
                } else {
                    if ($fieldMeta['identifier']) {
                        $setter = function ($field, $value) {
                            $this->$field = $value;
                        };

                        $setter = \Closure::bind($setter, $object, $object);
                        $setter($fieldMeta['name'], $value);
                    } else {
                        $object->$setter($value);
                    }
                }
            }
        }

        return $object;
    }
}
