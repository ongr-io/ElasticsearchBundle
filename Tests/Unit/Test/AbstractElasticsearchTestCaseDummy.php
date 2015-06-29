<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Test;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

/**
 * AbstractElasticsearchTestCase dummy class for testing.
 */
class AbstractElasticsearchTestCaseDummy extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getContainer($rebuild = false, $kernelOptions = [])
    {
        return parent::getContainer($rebuild, $kernelOptions);
    }
}
