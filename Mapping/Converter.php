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
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This class converts array to document object.
 */
class Converter
{
    private $documentParser;
    private $serializer;

    public function __construct(DocumentParser $documentParser, Serializer $serializer)
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

    public function convertDocumentToArray($document): array
    {
        return [];
    }
}
