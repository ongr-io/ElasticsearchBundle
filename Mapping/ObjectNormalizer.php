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

use Doctrine\Common\Cache\Cache;
use ONGR\ElasticsearchBundle\Result\ObjectIterator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;

class ObjectNormalizer extends SymfonyObjectNormalizer
{
    private $cache;
    private $converter;

    public function __construct(
        Cache $cache,
        Converter $converter,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(null, $nameConverter, $propertyAccessor, null, null, null, []);

        $this->cache = $cache;
        $this->converter = $converter;
    }

    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $embeddedFields = $this->cache->fetch(DocumentParser::EMBEDDED_CACHED_FIELDS);

        $class = $embeddedFields[get_class($object)][$attribute] ?? null;

        try {
            if ($class && is_array($value)) {
                $value = new ObjectIterator($class, $value, $this->converter, $this->serializer);
            }

            $this->propertyAccessor->setValue($object, $attribute, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }
}
