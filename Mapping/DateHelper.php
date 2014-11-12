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
 * Helps to format elasticsearch time string to interval in seconds.
 */
class DateHelper
{
    /**
     * Parses elasticsearch type of string into milliseconds.
     *
     * @param string $timeString
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public static function parseString($timeString)
    {
        $results = [];
        preg_match_all('/(\d+)([a-zA-Z]+)/', $timeString, $results);
        $values = $results[1];
        $units = $results[2];

        if (count($values) != count($units) || count($values) == 0) {
            throw new \InvalidArgumentException("Invalid time string '{$timeString}'.");
        }

        $result = 0;
        foreach ($values as $key => $value) {
            $result += $value * self::charToInterval($units[$key]);
        }

        return $result;
    }

    /**
     * Converts a string to time interval.
     *
     * @param string $value
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    private static function charToInterval($value)
    {
        switch($value) {
            case 'w':
                return 604800000;
            case 'd':
                return 86400000;
            case 'h':
                return 3600000;
            case 'm':
                return 60000;
            case 'ms':
                return 1;
            default:
                throw new \InvalidArgumentException("Unknown time unit '{$value}'.");
        }
    }
}
