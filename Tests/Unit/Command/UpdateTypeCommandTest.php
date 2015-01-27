<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Command;

use ONGR\ElasticsearchBundle\Command\TypeUpdateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

/**
 * Unit tests for UpdateTypeCommand.
 */
class UpdateTypeCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if exception is thrown when manager return unknown result.
     *
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected boolean value from Connection::updateMapping()
     */
    public function testExecuteUnexpectedValue()
    {
        $managerMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'updateMapping'])
            ->getMock();

        $managerMock->expects($this->once())->method('getConnection')->willReturnSelf();
        $managerMock->expects($this->once())->method('updateMapping')->willReturn(3);

        /** @var TypeUpdateCommand|\PHPUnit_Framework_MockObject_MockObject $command */
        $command = $this->getMockBuilder('ONGR\ElasticsearchBundle\Command\TypeUpdateCommand')
            ->setMethods(['clearMappingCache'])
            ->getMock();
        $command->expects($this->once())->method('clearMappingCache')->willReturn($managerMock);

        $app = new Application();
        $app->add($command);

        $commandToTest = $app->find('es:type:update');
        $commandTester = new CommandTester($commandToTest);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--force' => true,
            ]
        );
    }

    /**
     * Check if correct value is returned when force flag isn't set.
     */
    public function testForceDisabled()
    {
        $command = new TypeUpdateCommand();
        $app = new Application();
        $app->add($command);

        $commandToTest = $app->find('es:type:update');
        $commandTester = new CommandTester($commandToTest);
        $result = $commandTester->execute(
            [
                'command' => $command->getName(),
            ]
        );

        $this->assertEquals(1, $result);
    }

    /**
     * Check if correct value is returned when undefined type is specified.
     */
    public function testUndefinedType()
    {
        /** @var Container|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['getParameter', 'get', 'has'])
            ->disableOriginalConstructor()
            ->getMock();
        $containerMock->expects($this->any())->method('getParameter')->willReturn([]);
        $containerMock->expects($this->any())->method('get')->willReturnSelf();
        $containerMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $command = new TypeUpdateCommand();
        $command->setContainer($containerMock);

        $app = new Application();
        $app->add($command);

        $commandToTest = $app->find('es:type:update');
        $commandTester = new CommandTester($commandToTest);
        $result = $commandTester->execute(
            [
                'command' => $command->getName(),
                '--force' => true,
                '--type' => 'unkown',
            ]
        );

        $this->assertEquals(2, $result);
    }
}
