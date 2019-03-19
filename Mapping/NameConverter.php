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
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class NameConverter implements AdvancedNameConverterInterface
{
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function normalize($propertyName, string $class = null, string $format = null, array $context = [])
    {
        $fields = $this->cache->fetch(DocumentParser::OBJ_CACHED_FIELDS);

        if (isset($fields[$class])) {
            return $fields[$class][$propertyName] ?? $propertyName;
        }

        return $propertyName;
    }

    public function denormalize($propertyName, string $class = null, string $format = null, array $context = [])
    {
        $fields = $this->cache->fetch(DocumentParser::ARRAY_CACHED_FIELDS);

        if (isset($fields[$class])) {
            return $fields[$class][$propertyName] ?? $propertyName;
        }

        return $propertyName;
    }
}
