<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result\Suggestion\Option;

/**
 * Option data class.
 */
class SimpleOption
{
    /**
     * @var string
     */
    private $text;

    /**
     * @var float
     */
    private $score;

    /**
     * Constructor.
     *
     * @param string $text
     * @param float  $score
     */
    public function __construct($text, $score)
    {
        $this->score = $score;
        $this->text = $text;
    }

    /**
     * Suggester score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Suggested text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets option score.
     *
     * @param float $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }
}
