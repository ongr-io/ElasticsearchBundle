<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Command;

use ONGR\ElasticsearchBundle\Client\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Abstract command which helps executing where connections are required.
 */
abstract class AbstractConnectionAwareCommand extends AbstractElasticsearchCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'connection',
                null,
                InputOption::VALUE_REQUIRED,
                'Connection name',
                'default'
            );
    }

    /**
     * Returns elasticsearch connection by name.
     *
     * @param string $name Connection name.
     *
     * @return Connection
     */
    protected function getConnection($name)
    {
        return $this->getManager($this->getManagerNameByConnection($name))->getConnection();
    }

    /**
     * Returns manager name which is using passed connection.
     *
     * @param string $name Connection name.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    private function getManagerNameByConnection($name)
    {
        foreach ($this->getContainer()->getParameter('es.managers') as $managerName => $params) {
            if ($params['connection'] === $name) {
                return $managerName;
            }
        }

        throw new \RuntimeException(
            sprintf('Connection named %s is not used by any manager. Check your configuration.', $name)
        );
    }
}
