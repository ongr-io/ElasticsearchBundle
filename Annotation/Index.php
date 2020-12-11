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

use Doctrine\Common\Annotations\Annotation\Attributes;

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
     */
    public $alias;

    public $hosts;

    public $numberOfShards = 5;

    public $numberOfReplicas = 1;

    /**
     * We strongly recommend to not use this parameter in the index annotation. By default it will be set as `_doc`
     * type name. Eventually it will be removed.
     *
     * @deprecated will be removed in v7 since there will be no more types in the indexes.
     */
    public $typeName = '_doc';

    /**
     * You can select one of your indexes to be default. Useful for cli commands when you don't
     *   need to define an alias name. If default is not set the first index found will be set as default one.
     */
    public $default = false;
}
