<?php

namespace ONGR\ElasticsearchBundle\Result\Suggestion\Option;

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ONGR\ElasticsearchBundle\Result\Suggestion\Option;

/**
 * Data holder for phrase option.
 */
class PhraseOption extends SimpleOption
{
    /**
     * @var string
     */
    private $highlighted;

    /**
     * Constructor.
     *
     * @param string $text
     * @param float  $score
     * @param string $highlighted
     */
    public function __construct($text, $score, $highlighted)
    {
        $this->highlighted = $highlighted;
        parent::__construct($text, $score);
    }

    /**
     * Returns highlighted suggestion.
     *
     * @return string
     */
    public function getHighlighted()
    {
        return $this->highlighted;
    }
}
