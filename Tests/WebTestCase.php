<?php
namespace ONGR\ElasticsearchBundle\Tests;

class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    public static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return \AppKernel::class;
    }
}
