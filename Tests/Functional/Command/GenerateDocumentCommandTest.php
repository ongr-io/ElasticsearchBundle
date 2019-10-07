<?php

namespace ONGR\ElasticsearchBundle\Tests\Functional\Command;

use ONGR\ElasticsearchBundle\Command\DocumentGenerateCommand;
use ONGR\ElasticsearchBundle\Tests\WebTestCase;

class GenerateDocumentCommandTest extends WebTestCase
{
    /**
     * Tests if exception is thrown when no interaction is set
     *
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteException()
    {
        $this->markTestSkipped('Document generator will be implemented later.');

//        $app = new Application();
//        $app->add($this->getCommand());
//
//        $command = $app->find('ongr:es:document:generate');
//
//        $tester = new CommandTester($command);
//        $tester->execute(['command' => $command->getName()], ['interactive' => false]);
//        $tester->execute(
//            ['command' => $command->getName(), '--no-interaction' => true],
//            ['interactive' => false]
//        );
    }

    /**
     * @return DocumentGenerateCommand
     */
    private function getCommand()
    {
        $command = new DocumentGenerateCommand();
        $command->setContainer(self::createClient()->getContainer());

        return $command;
    }
}
