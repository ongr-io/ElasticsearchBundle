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

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\ORM\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update mapping.
 */
class TypeUpdateCommand extends AbstractElasticsearchCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('es:type:update')
            ->setDescription('Creates mapping for elasticsearch')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command.'
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Set this parameter to update only a specific type'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('force')) {
            $output->writeln('Option --force has to be used to update mapping.');

            return 1;
        }

        try {
            $manager = $this->clearMappingCache($input->getOption('manager'), $input->getOption('type'));
        } catch (\UnderflowException $ex) {
            $output->writeln("<error>Undefined document type {$input->getOption('type')}</error>");

            return 2;
        }

        $result = $manager->getConnection()->updateMapping();
        if ($result === true) {
            $output->writeln('<info>Types updated.</info>');
        } elseif ($result === false) {
            $output->writeln('<info>Types are already up to date.</info>');
        } else {
            throw new \UnexpectedValueException('Expected boolean value from Connection::updateMapping()');
        }

        return 0;
    }

    /**
     * Updates mapping information for the given manager.
     *
     * @param string $managerName
     * @param string $type
     *
     * @return Manager
     * @throws \UnderflowException
     */
    protected function clearMappingCache($managerName, $type = '')
    {
        $manager = $this->getManager($managerName);
        /** @var MetadataCollector $collector */
        $collector = $this->getContainer()->get('es.metadata_collector');

        $mappings = [];

        foreach ($this->getContainer()->getParameter('es.managers') as $name => $setting) {
            if (!$managerName || $managerName == 'default' || $name == $managerName) {
                foreach ($setting['mappings'] as $bundle) {
                    $mappings = array_replace_recursive(
                        $mappings,
                        $collector->getMapping($bundle)
                    );
                }
            }
        }

        if (!empty($type)) {
            if (!isset($mappings[$type])) {
                throw new \UnderflowException("Document type {$type} not found");
            }
            $manager->getConnection()->setMapping($type, $mappings[$type]);
        } else {
            $manager->getConnection()->forceMapping($mappings);
        }

        return $manager;
    }
}
