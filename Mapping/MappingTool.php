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
    private $ignoredFields = [
        'type' => 'object',
        '_routing' => ['required' => true],
        'format' => 'dateOptionalTime',
        'max_input_length' => 50,
        'preserve_separators' => true,
        'preserve_position_increments' => true,
        'analyzer' => 'simple',
        'payloads' => false,
    ];

    /**
     * @var array
     */
    protected $formatFields = [
        '_ttl' => 'handleTime',
        'precision' => 'handlePrecision',
    ];

    /**
     * @var array
     */
    private $removedTypes = [];

    /**
     * @var array
     */
    private $updatedTypes = [];

    /**
     * Compares two mappings and returns true if changes detected.
     * 
     * @param array $oldMapping
     * @param array $newMapping
     * 
     * @return bool
     */
    public function checkMapping($oldMapping, $newMapping)
    {
        $updated = false;

        // Find out which types don't exist anymore.
        $typeDiff = array_diff_key($oldMapping, $newMapping);
        foreach ($typeDiff as $oldTypeName => $oldType) {
            $this->removedTypes[] = $oldTypeName;
            $updated = true;
        }

        // Search for differences in types.
        foreach ($newMapping as $type => $properties) {
            $diff = null;
            if (array_key_exists($type, $oldMapping)) {
                $diff = $this->symDifference($properties, $oldMapping[$type]);
            }

            // Empty array type properties hasn't changed, NULL - new type.
            if ($diff !== [] || $diff === null) {
                $this->updatedTypes[$type] = $properties;
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Returns symmetric difference.
     *
     * @param array $oldMapping
     * @param array $newMapping
     *
     * @return array
     */
    public function symDifference($oldMapping, $newMapping)
    {
        $oldMapping = $this->arrayFilterRecursive($oldMapping);
        $newMapping = $this->arrayFilterRecursive($newMapping);

        return array_replace_recursive(
            $this->recursiveDiff($oldMapping, $newMapping),
            $this->recursiveDiff($newMapping, $oldMapping)
        );
    }

    /**
     * Retuns type name which has been removed.
     * 
     * @return array
     */
    public function getRemovedTypes()
    {
        return $this->removedTypes;
    }

    /**
     * Returns type names with new properties which has been updated.
     * 
     * @return array
     */
    public function getUpdatedTypes()
    {
        return $this->updatedTypes;
    }

    /**
     * Recursively computes the difference of arrays.
     *
     * @param array $compareFrom
     * @param array $compareAgainst
     *
     * @return array
     */
    private function recursiveDiff($compareFrom, $compareAgainst)
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

    /**
     * Handles precision.
     *
     * @param array|string $value
     *
     * @return array
     */
    private function handlePrecision($value)
    {
        // TODO: currently precision is not checked. Issue #46.

        return [];
    }
}
