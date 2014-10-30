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

use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

/**
 * ElasticsearchTestCase dummy class for testing.
 */
class ElasticsearchTestCaseDummy extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return parent::getContainer();
    }
}
