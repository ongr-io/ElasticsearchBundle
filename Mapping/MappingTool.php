<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping;

/**
 * Mapping tool for comparing.
 */
class MappingTool
{
    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var array
     */
    protected $ignoredFields = [
        'type' => 'object',
        '_routing' => ['required' => true],
        'format' => 'dateOptionalTime',
    ];

    /**
     * @var array
     */
    protected $formatFields = [
        '_ttl' => 'handleTime',
    ];

    /**
     * @param array $mapping
     */
    public function __construct($mapping = [])
    {
        $this->setMapping($mapping);
    }

    /**
     * Returns symmetric difference.
     *
     * @param array $newMapping
     *
     * @return array
     */
    public function symDifference($newMapping)
    {
        $newMapping = $this->arrayFilterRecursive($newMapping);

        return array_replace_recursive(
            $this->recursiveDiff($this->mapping, $newMapping),
            $this->recursiveDiff($newMapping, $this->mapping)
        );
    }

    /**
     * @param array $mapping
     */
    public function setMapping($mapping)
    {
        $this->mapping = $this->arrayFilterRecursive($mapping);
    }

    /**
     * Recursively computes the difference of arrays.
     *
     * @param array $compareFrom
     * @param array $compareAgainst
     *
     * @return array
     */
    protected function recursiveDiff($compareFrom, $compareAgainst)
    {
        $out = [];

        foreach ($compareFrom as $mKey => $mValue) {
            if (array_key_exists($mKey, $compareAgainst)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->recursiveDiff($mValue, $compareAgainst[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $out[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $compareAgainst[$mKey]) {
                        $out[$mKey] = $mValue;
                    }
                }
            } else {
                $out[$mKey] = $mValue;
            }
        }

        // Check for empty arrays.
        foreach ($out as $key => $element) {
            if ($this->countRecursiveScalars($element) == 0) {
                unset($out[$key]);
            }
        }

        return $out;
    }

    /**
     * Counts scalar values recursively in array.
     *
     * @param mixed $array
     *
     * @return int
     */
    private function countRecursiveScalars($array)
    {
        $count = 0;

        if (!is_array($array)) {
            return 1;
        }

        foreach ($array as $element) {
            if (is_array($element)) {
                $count += $this->countRecursiveScalars($element);
            } else {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Filters fields not returned by elastica mapping e.g. 'type' of value 'object'.
     *
     * @param array $array
     *
     * @return array
     */
    private function arrayFilterRecursive(array $array)
    {
        foreach ($array as $key => $value) {
            if (array_key_exists($key, $this->ignoredFields) && $this->ignoredFields[$key] == $array[$key]) {
                unset($array[$key]);
                continue;
            }

            if (array_key_exists($key, $this->formatFields)) {
                $array[$key] = call_user_func([$this, $this->formatFields[$key]], $array[$key]);
            }

            if (is_array($array[$key])) {
                $array[$key] = $this->arrayFilterRecursive($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Change time formats to fit elasticsearch.
     *
     * @param array $value
     *
     * @return array
     */
    private function handleTime($value)
    {
        if (!isset($value['default']) || !is_string($value['default'])) {
            return $value;
        }

        $value['default'] = DateHelper::parseString($value['default']);

        return $value;
    }
}
