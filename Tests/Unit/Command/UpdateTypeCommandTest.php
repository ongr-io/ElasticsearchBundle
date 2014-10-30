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
    public function testExecute()
    {
        /** @var TypeUpdateCommand|\PHPUnit_Framework_MockObject_MockObject $command */
        $command = $this->getMockBuilder('ONGR\ElasticsearchBundle\Command\TypeUpdateCommand')
            ->setMethods(['clearMappingCache', 'getConnection', 'updateMapping'])
            ->getMock();
        $command->expects($this->once())->method('clearMappingCache')->willReturnSelf();
        $command->expects($this->once())->method('getConnection')->willReturnSelf();
        $command->expects($this->once())->method('updateMapping')->willReturn(3);

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
}
