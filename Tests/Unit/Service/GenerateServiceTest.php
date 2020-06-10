<?php

namespace ONGR\ElasticsearchBundle\Tests\Unit\Generator;

use ONGR\ElasticsearchBundle\Generator\DocumentGenerator;
use ONGR\ElasticsearchBundle\Service\GenerateService;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;
use Symfony\Component\Filesystem\Filesystem;

class GenerateServiceTest extends TestCase
{
    use SetUpTearDownTrait;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * {@inheritdoc}
     */
    public function doSetUp()
    {
        $this->tmpDir = sys_get_temp_dir() . '/ongr';

        $this->filesystem = new Filesystem();
        $this->filesystem->remove($this->tmpDir);
    }

    /**
     * {@inheritdoc}
     */
    public function doTearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testGenerate()
    {
        $service = new GenerateService(new DocumentGenerator(), $this->filesystem);

        $service->generate(
            $this->getBundle(),
            'Foo',
            'Document',
            'foo',
            [
                [
                    'field_name' => 'test',
                    'annotation' => 'property',
                    'visibility' => 'private',
                    'property_type' => 'string',
                    'property_name' => 'testProperty',
                    'property_options' => 'test',
                ],
                [
                    'field_name' => 'embedded',
                    'visibility' => 'protected',
                    'annotation' => 'embedded',
                    'property_class' => 'TestBundle:Product',
                    'property_multiple' => true,
                ]
            ]
        );

        $this->assertFileExists($this->getBundle()->getPath() . '/Document/Foo.php');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getBundle()
    {
        $bundle = $this->createMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->will($this->returnValue($this->tmpDir));
        $bundle->expects($this->any())->method('getName')->will($this->returnValue('FooBarBundle'));
        $bundle->expects($this->any())->method('getNamespace')->will($this->returnValue('Foo\BarBundle'));

        return $bundle;
    }
}
