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
    public function filter($extraExclude = [])
    {
        $data = parent::filter(['context']);

        /** @var AbstractContext $singleContext */
        foreach ($this->context as $singleContext) {
            $data['context'][$singleContext->name] = $singleContext->filter();
        }

        return $data;
    }
}
