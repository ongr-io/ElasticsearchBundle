<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Annotation\Suggester;

/**
 * Class for completion suggester.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class CompletionSuggesterProperty extends AbstractSuggesterProperty
{
    /**
     * @var string
     */
    public $index_analyzer;

    /**
     * @var string
     */
    public $search_analyzer;

    /**
     * @var int
     */
    public $preserve_separators;

    /**
     * @var bool
     */
    public $preserve_position_increments;

    /**
     * @var int
     */
    public $max_input_length;

    /**
     * @var bool
     */
    public $payloads;

    /**
     * {@inheritdoc}
     */
    public function filter()
    {
        $data = array_merge(parent::filter(), array_filter(get_object_vars($this)));

        return array_diff_key(
            $data,
            array_flip(['name', 'objectName'])
        );
    }
}
