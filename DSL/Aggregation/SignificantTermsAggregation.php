<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DSL\Aggregation;

use ONGR\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;
use ONGR\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Class representing significant terms aggregation.
 */
class SignificantTermsAggregation extends AbstractAggregation
{
    use BucketingTrait;

    const HINT_MAP = 'map';
    const HINT_GLOBAL_ORDINALS = 'global_ordinals';
    const HINT_GLOBAL_ORDINALS_HASH = 'global_ordinals_hash';

    /**
     * @var int
     */
    private $minDocCount;

    /**
     * @var array
     */
    private $mutualInformation;

    /**
     * @var array
     */
    private $chiSquare;

    /**
     * @var array Google normalized distance.
     */
    private $gnd;

    /**
     * @var bool
     */
    private $percentage;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $shardSize;

    /**
     * @var int
     */
    private $shardMinDocCount;

    /**
     * @var BuilderInterface
     */
    private $backgroundFilter;

    /**
     * @var string
     */
    private $executionHint;

    /**
     * @var bool JLH score.
     */
    private $jlh;

    /**
     * @return bool
     */
    public function isJlh()
    {
        return $this->jlh;
    }

    /**
     * @param bool $jlh
     */
    public function setJlh($jlh)
    {
        $this->jlh = $jlh;
    }

    /**
     * @return int
     */
    public function getShardMinDocCount()
    {
        return $this->shardMinDocCount;
    }

    /**
     * @param int $shardMinDocCount
     */
    public function setShardMinDocCount($shardMinDocCount)
    {
        $this->shardMinDocCount = $shardMinDocCount;
    }

    /**
     * @return int
     */
    public function getShardSize()
    {
        return $this->shardSize;
    }

    /**
     * @param int $shardSize
     */
    public function setShardSize($shardSize)
    {
        $this->shardSize = $shardSize;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return array
     */
    public function getMutualInformation()
    {
        return $this->mutualInformation;
    }

    /**
     * @return array
     */
    public function getChiSquare()
    {
        return $this->chiSquare;
    }

    /**
     * @return array
     */
    public function getGnd()
    {
        return $this->gnd;
    }

    /**
     * @param bool $backgroundIsSuperset
     */
    public function setGnd($backgroundIsSuperset = false)
    {
        $this->gnd = ['background_is_superset' => $backgroundIsSuperset];
    }

    /**
     * @return bool
     */
    public function isPercentage()
    {
        return $this->percentage;
    }

    /**
     * @param bool $percentage
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
    }

    /**
     * Mutual information parameter.
     *
     * @param bool $includeNegatives
     * @param bool $backgroundIsSuperset
     */
    public function setMutualInformation($includeNegatives = true, $backgroundIsSuperset = false)
    {
        $this->mutualInformation = [
            'include_negatives' => $includeNegatives,
            'background_is_superset' => $backgroundIsSuperset,
        ];
    }

    /**
     * Chi square parameter.
     *
     * @param bool $includeNegatives
     * @param bool $backgroundIsSuperset
     */
    public function setChiSquare($includeNegatives = true, $backgroundIsSuperset = false)
    {
        $this->chiSquare = [
            'include_negatives' => $includeNegatives,
            'background_is_superset' => $backgroundIsSuperset,
        ];
    }

    /**
     * @return int
     */
    public function getMinDocCount()
    {
        return $this->minDocCount;
    }

    /**
     * @param int $minDocCount
     */
    public function setMinDocCount($minDocCount)
    {
        $this->minDocCount = $minDocCount;
    }

    /**
     * @return string
     */
    public function getExecutionHint()
    {
        return $this->executionHint;
    }

    /**
     * @param string $executionHint
     */
    public function setExecutionHint($executionHint = self::HINT_MAP)
    {
        $this->executionHint = $executionHint;
    }

    /**
     * @return BuilderInterface
     */
    public function getBackgroundFilter()
    {
        return $this->backgroundFilter;
    }

    /**
     * @param BuilderInterface $backgroundFilter
     *
     * @throws \InvalidArgumentException
     */
    public function setBackgroundFilter($backgroundFilter)
    {
        if ($backgroundFilter instanceof BuilderInterface) {
            $this->backgroundFilter = $backgroundFilter;
        } else {
            throw new \InvalidArgumentException('Background filter should be an instance of BuilderInterface');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'significant_terms';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        $out = array_filter(
            [
                'field' => $this->getField(),
                'shard_min_doc_count' => $this->getShardMinDocCount(),
                'size' => $this->getSize(),
                'shard_size' => $this->getShardSize(),
                'execution_hint' => $this->getExecutionHint(),
                'chi_square' => $this->getChiSquare(),
                'mutual_information' => $this->getMutualInformation(),
                'min_doc_count' => $this->getMinDocCount(),
                'gnd' => $this->getGnd(),
            ]
        );

        if ($this->getBackgroundFilter()) {
            $out['background_filter'] = [
                $this->getBackgroundFilter()->getType() => $this->getBackgroundFilter()->toArray(),
            ];
        }

        if ($this->isPercentage()) {
            $out['percentage'] = new \stdClass();
        }

        if ($this->isJlh()) {
            $out['jlh'] = new \stdClass();
        }

        return $out;
    }
}
