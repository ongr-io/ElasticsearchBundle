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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Elasticsearch command used for dropping types.
 */
class TypeDropCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:type:drop')
            ->setDescription('Updates elasticsearch index mappings.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Specific types to drop.',
                []
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
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

        $manager = $input->getOption('manager');
        $type = $input->getOption('type');

        $connection = $this->getManager($manager)->getConnection();
        $status = $connection->dropTypes($type);

        if ($status) {
            $message = sprintf(
                '<info>Dropped `</info><comment>%s</comment><info>` type(s) for manager named '
                . '`</info><comment>%s</comment><info>`.</info>',
                empty($type) ? 'all' : implode('</comment><info>`, `</info><comment>', $type),
                $manager
            );
        } else {
            if (empty($type)) {
                $typeString = '';
            } else {
                $typeString = ' `<comment>' . implode('</comment><info>`, `</info><comment>', $type) . '</comment>`';
            }
            $message = sprintf(
                '<info>Manager `</info><comment>%s</comment><info>` does not contain%s type(s) information.</info>',
                $manager,
                $typeString
            );
        }

        $output->writeln($message);

        return 0;
    }
}
