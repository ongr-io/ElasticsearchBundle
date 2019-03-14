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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;

class ObjectNormalizer extends SymfonyObjectNormalizer
{
    protected $objectClassResolver;

    public function __construct(array $objectClassResolver = [], NameConverterInterface $nameConverter = null, PropertyAccessorInterface $propertyAccessor = null)
    {
        parent::__construct(null, $nameConverter, $propertyAccessor, null, null, null, []);

        $this->objectClassResolver = $objectClassResolver;
    }

    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        try {
            $this->propertyAccessor->setValue($object, $attribute, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }
}
