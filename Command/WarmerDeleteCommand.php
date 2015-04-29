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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command removes warmers from elasticsearch index.
 */
class WarmerDeleteCommand extends AbstractManagerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ongr:es:warmer:delete')
            ->setDescription('Removes warmers from elasticsearch index.')
            ->addArgument(
                'names',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Warmer names',
                []
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $names = $input->getArgument('names');
        $status = $this->getManager($input->getOption('manager'))->getConnection()->deleteWarmers($names);

        if ($status === false) {
            $message = '<info>There are no warmers registered for manager named<info> <comment>`%s`</comment>!';
        } elseif (empty($names)) {
            $message = '<info>All warmers have been deleted from manager named<info> <comment>`%s`</comment>';
        } else {
            $callback = function ($val) {
                return '`' . $val . '`';
            };
            $message = '<comment>' . implode(', ', array_map($callback, $names)) . '</comment>'
                . '<info> warmer(s) have been deleted from manager named</info> <comment>`%s`</comment>';
        }

        $output->writeln(sprintf($message, $input->getOption('manager')));

        return 0;
    }
}
