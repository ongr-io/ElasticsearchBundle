<?php

namespace ONGR\ElasticsearchBundle\Annotation\Suggester;

use ONGR\ElasticsearchBundle\Annotation\Suggester\Context\AbstractContext;

/**
 * Class for context suggester annotations.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class ContextSuggesterProperty extends AbstractSuggesterProperty
{
    /**
     * @var array<\ONGR\ElasticsearchBundle\Annotation\Suggester\Context\AbstractContext>
     */
    public $context;

    /**
     * {@inheritdoc}
     */
    public function filter()
    {
        $data = array_merge(parent::filter(), array_filter(get_object_vars($this)));

        $data = array_diff_key(
            $data,
            array_flip(['name', 'objectName', 'context'])
        );

        /** @var AbstractContext $singleContext */
        foreach ($this->context as $singleContext) {
            $data['context'][$singleContext->name] = $singleContext->filter();
        }

        return $data;
    }
}
