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
final class Index implements DumperInterface
{
    /**
     * @var string
     */
    public $indexName;

    /**
     * @deprecated will be removed in v7 since there will be no more types in the indexes.
     * @var string
     */
    public $typeName;

    /**
     * Options is a custom configuration to pass to the client when index is created.
     *  e.g. you can use it for the dynamic templates
     *
     * @var array
     */
    public $options = [];

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        return array_diff_key(
            $this->options,
            $exclude
        );
    }
}
