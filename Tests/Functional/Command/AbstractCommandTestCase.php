<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Command;

use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Helper test case for testing commands.
 */
class AbstractCommandTestCase extends WebTestCase
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return self::createClient()->getContainer();
    }

    /**
     * @param string $name
     *
     * @return Manager
     */
    protected function getManager($name)
    {
        $manager = ($name == 'default') || (empty($name)) ? 'es.manager' : sprintf('es.manager.%s', $name);

        return $this->getContainer()->get($manager);
    }
}
