<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Test;

/**
 * A helper used to check if array contains a sub array.
 *
 * @deprecated use assertArraySubset method from phpunit > 4.4
 */
trait TestHelperTrait
{
    /**
     * Check if one array is subset of another.
     *
     * @param array $needle   Subset.
     * @param array $haystack Set.
     */
    protected function assertArrayContainsArray($needle, $haystack)
    {
        foreach ($needle as $key => $val) {
            \PHPUnit_Framework_Assert::assertArrayHasKey($key, $haystack);
            if (is_array($val)) {
                $this->assertArrayContainsArray($val, $haystack[$key]);
            } else {
                \PHPUnit_Framework_Assert::assertEquals($val, $haystack[$key]);
            }
        }
    }

    /**
     * Check if one array has the values of other array.
     *
     * Used to compare unsorted single dimension arrays.
     *
     * @param array $needle              Subset.
     * @param array $haystack            Set.
     * @param bool  $shouldFitCompletely Set should only contain specified values.
     */
    protected function assertArrayContainsArrayValues($needle, $haystack, $shouldFitCompletely = false)
    {
        foreach ($needle as $val) {
            $key = array_search($val, $haystack);
            \PHPUnit_Framework_Assert::assertNotFalse($key, "Failed asserting that array contains {$val}.");
            unset($haystack[$key]);
        }

        if ($shouldFitCompletely) {
            \PHPUnit_Framework_Assert::assertTrue(empty($haystack), 'Failed asserting that array contains all values.');
        }
    }
}
