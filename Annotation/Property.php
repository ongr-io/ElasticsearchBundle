<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property extends AbstractAnnotation implements PropertiesAwareInterface
{
    use NameAwareTrait;
    use PropertyTypeAwareTrait;

    public $analyzer;
    public $searchAnalyzer;
    public $searchQuoteAnalyzer;
}
