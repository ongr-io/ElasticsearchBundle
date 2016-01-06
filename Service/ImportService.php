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
    /**
     * Imports Elasticsearch index data.
     *
     * @param Manager         $manager
     * @param string          $filename
     * @param OutputInterface $output
     * @param int             $bulkSize
     * @param bool            $isGzip
     */
    public function importIndex(
        Manager $manager,
        $filename,
        OutputInterface $output,
        $bulkSize,
        $isGzip
    ) {
        $reader = $this->getReader($manager, $this->getFilePath($filename), false, $isGzip);

        $progress = new ProgressBar($output, $reader->count());
        $progress->setRedrawFrequency(100);
        $progress->start();

        foreach ($reader as $key => $document) {
            $data = $document['_source'];
            $data['_id'] = $document['_id'];

            if (array_key_exists('fields', $document)) {
                $data = array_merge($document['fields'], $data);
            }

            $manager->bulk('index', $document['_type'], $data);

            if (($key + 1) % $bulkSize == 0) {
                $manager->commit();
            }

            $progress->advance();
        }

        $manager->commit();

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
     * @param string   $dataType
     *
     * @return JsonReader
     */
    protected function getReader($manager, $filename, $convertDocuments, $dataType)
    {
        return new JsonReader($manager, $filename, $convertDocuments, $dataType);
    }
}
