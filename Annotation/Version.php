<?php
namespace ONGR\ElasticsearchBundle\Annotation;

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class Version implements MetaField
{

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '_version';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return [];
    }
}
