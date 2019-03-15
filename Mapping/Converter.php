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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This class converts array to document object.
 */
class Converter
{
    use SerializerAwareTrait;

    private $documentParser;

    public function __construct(DocumentParser $documentParser)
    {
        $this->documentParser = $documentParser;
    }

    public function convertArrayToDocument(string $namespace, array $raw, Serializer $serializer)
    {
        return $serializer->denormalize($raw, $namespace);
    }

    public function convertDocumentToArray($document, Serializer $serializer): array
    {
        return $serializer->normalize($document, 'array');
    }
}
