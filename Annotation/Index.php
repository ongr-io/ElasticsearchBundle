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

use ONGR\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Annotation to mark a class as an Elasticsearch index.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Index extends AbstractAnnotation
{
    /**
     * Index alias name. By default the index name will be created with the timestamp appended to the alias.
     *
     * @var string
     */
    public $alias;

    /**
     * We strongly reccomend to not use this parameter in the index annotation. By default it will be set as `_doc`
     * type name. Eventually it will be removed.
     *
     * @deprecated will be removed in v7 since there will be no more types in the indexes.
     */
    public $typeName = '_doc';
}
