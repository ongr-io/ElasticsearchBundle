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
}
