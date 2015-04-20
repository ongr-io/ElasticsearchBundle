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
class TypeUpdateCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:type:update')
            ->setDescription('Updates elasticsearch index mappings.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set this parameter to update only a specific types',
                []
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('force')) {
            $output->writeln(
                '<error>ATTENTION:</error> This action should not be used in production environment.'
                . "\n\nOption --force has to be used to drop type(s)."
            );

            return 1;
        }

        $managerName = $input->getOption('manager');
        $types = $input->getOption('type');

        $result = $this
            ->getManager($input->getOption('manager'))
            ->getConnection()
            ->updateTypes($types);

        $typesOutput = empty($types) ? 'all' : implode('</comment><info>`, `</info><comment>', $types);

        switch ($result) {
            case 1:
                $message = sprintf(
                    '<info>`</info><comment>%s</comment><info>` type(s) have been updated for manager'
                    . ' named `</info><comment>%s</comment><info>`.</info>',
                    $typesOutput,
                    $managerName
                );
                break;
            case 0:
                $message = sprintf(
                    '<info>`</info><comment>%s</comment><info>` type(s) are already up to date for manager'
                    . ' named `</info><comment>%s</comment><info>`.</info>',
                    $typesOutput,
                    $managerName
                );
                break;
            case -1:
                $message = sprintf(
                    '<info>No mapping was found%s in </info>`<comment>%s</comment>`<info> manager.</info>',
                    empty($types) ? '' : sprintf(' for `</info><comment>%s</comment><info>` types', $typesOutput),
                    $managerName
                );
                break;
            default:
                $message = 'Message not found.';
                break;
        }

        $output->writeln($message);

        return 0;
    }
}
