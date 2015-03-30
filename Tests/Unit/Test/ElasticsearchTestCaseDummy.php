<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Test;

use Ongr\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * ElasticsearchTestCase dummy class for testing.
 */
class ElasticsearchTestCaseDummy extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getContainer($rebuild = false, $kernelOptions = [])
    {
        return parent::getContainer($rebuild, $kernelOptions);
    }
}
