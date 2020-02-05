<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

use ONGR\ElasticsearchBundle\Service\Json\JsonReader;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ImportService class.
 */
class ImportService
{
    public function importIndex(
        IndexService $index,
        $filename,
        OutputInterface $output,
        $options
    ) {
        $reader = $this->getReader($index, $this->getFilePath($filename), $options);

        $progress = new ProgressBar($output, $reader->count());
        $progress->setRedrawFrequency(100);
        $progress->start();

        $bulkSize = $options['bulk-size'];
        foreach ($reader as $key => $document) {
            $data = $document['_source'];
            $data['_id'] = $document['_id'];

            if (array_key_exists('fields', $document)) {
                $data = array_merge($document['fields'], $data);
            }

            $index->bulk('index', $data);

            if (($key + 1) % $bulkSize == 0) {
                $index->commit();
            }

            $progress->advance();
        }

        $index->commit();

        $progress->finish();
        $output->writeln('');
    }

    /**
     * Returns a real file path.
     *
     * @param string $filename
     *
     * @return string
     */
    protected function getFilePath($filename)
    {
        if ($filename[0] == '/' || strstr($filename, ':') !== false) {
            return $filename;
        }

        return realpath(getcwd() . '/' . $filename);
    }

    protected function getReader(IndexService $manager, $filename, $options): JsonReader
    {
        return new JsonReader($manager, $filename, $options);
    }
}
