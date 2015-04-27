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

use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\Service\Json\JsonReader;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ImportService class.
 */
class ImportService
{
    /**
     * Imports Elasticsearch index data.
     *
     * @param Manager         $manager
     * @param string          $filename
     * @param bool            $raw
     * @param OutputInterface $output
     * @param int             $bulkSize
     *
     * @throws \Exception
     */
    public function importIndex($manager, $filename, $raw, OutputInterface $output, $bulkSize = 1000)
    {
        if (!$raw) {
            throw new \Exception('Currently only raw import is supported. Please set --raw flag to use it.');
        }

        $this->executeRawImport($manager, $this->getFilePath($filename), $output, $bulkSize);
    }

    /**
     * Executes a raw import.
     *
     * @param Manager         $manager
     * @param string          $filename
     * @param OutputInterface $output
     * @param int             $bulkSize
     */
    protected function executeRawImport($manager, $filename, $output, $bulkSize)
    {
        $reader = $this->getReader($manager, $filename, false);

        if (class_exists('\Symfony\Component\Console\Helper\ProgressBar')) {
            $progress = new ProgressBar($output, $reader->count());
            $progress->setRedrawFrequency(100);
            $progress->start();
        } else {
            $progress = new ProgressHelper();
            $progress->setRedrawFrequency(100);
            $progress->start($output, $reader->count());
        }

        foreach ($reader as $key => $document) {
            $data = $document['_source'];
            $data['_id'] = $document['_id'];

            $manager->getConnection()->bulk('index', $document['_type'], $data);

            if (($key + 1) % $bulkSize == 0) {
                $manager->commit();
            }

            $progress->advance();
        }

        if (($key + 1) % $bulkSize != 0) {
            $manager->commit();
        }

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
        if ($filename{0} == '/' || strstr($filename, ':') !== false) {
            return $filename;
        }

        return realpath(getcwd() . '/' . $filename);
    }

    /**
     * Prepares JSON reader.
     *
     * @param Manager $manager
     * @param string  $filename
     * @param bool    $convertDocuments
     *
     * @return JsonReader
     */
    protected function getReader($manager, $filename, $convertDocuments)
    {
        return new JsonReader($manager, $filename, $convertDocuments);
    }
}
