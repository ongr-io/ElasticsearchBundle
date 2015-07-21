<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractDocument;

/**
 * Class Highlight.
 * @ES\Document()
 */
class Highlight extends AbstractDocument
{
    /**
     * @var string
     * @ES\Property(type="string", name="plain_field")
     */
    protected $plainField;

    /**
     * @var string
     * @ES\Property(type="string", name="offsets_field", indexOptions="offsets")
     */
    protected $offsetsField;

    /**
     * @var string
     * @ES\Property(type="string", name="term_vector_field", termVector="with_positions_offsets")
     */
    protected $termVectorField;

    /**
     * @return string
     */
    public function getPlainField()
    {
        return $this->plainField;
    }

    /**
     * @param string $plainField
     *
     * @return $this
     */
    public function setPlainField($plainField)
    {
        $this->plainField = $plainField;

        return $this;
    }

    /**
     * @return string
     */
    public function getOffsetsField()
    {
        return $this->offsetsField;
    }

    /**
     * @param string $offsetsField
     *
     * @return $this
     */
    public function setOffsetsField($offsetsField)
    {
        $this->offsetsField = $offsetsField;

        return $this;
    }

    /**
     * @return string
     */
    public function getTermVectorField()
    {
        return $this->termVectorField;
    }

    /**
     * @param string $termVectorField
     *
     * @return $this
     */
    public function setTermVectorField($termVectorField)
    {
        $this->termVectorField = $termVectorField;

        return $this;
    }
}
