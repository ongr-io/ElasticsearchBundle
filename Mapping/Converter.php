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

use ONGR\ElasticsearchBundle\Annotation\NestedType;
use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This class converts array to document object.
 */
class Converter
{
    private $documentParser;
    private $serializer;

    public function __construct(DocumentParser $documentParser, SerializerInterface $serializer)
    {
        $this->documentParser = $documentParser;
        $this->serializer = $serializer;
    }

    public function convertArrayToDocument($rawData)
    {
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

        $indexName = '';

        $data = [];

        $class = $this->documentParser->getDocumentNamespace($indexName);

        return $this->serializer->denormalize($data, $class);

//        $object = $this->assignArrayToObject($rawData, new $metadata['namespace'](), $metadata['aliases']);
//        return $object;
    }

    public function convertDocumentToArray($rawData): array
    {

//        $object = $this->assignArrayToObject($rawData, new $metadata['namespace'](), $metadata['aliases']);
//        return $object;
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
                            continue;
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
                    case NestedType::NAME:
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
}
