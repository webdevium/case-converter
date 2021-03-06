<?php

use Jawira\CaseConverter\Glue\MacroCase;
use PHPUnit\Framework\TestCase;

class MacroCaseTest extends TestCase
{
    /**
     * @covers \Jawira\CaseConverter\Glue\MacroCase::glue
     */
    public function testGlue()
    {
        // Disabling constructor with one stub method
        $mock = $this->getMockBuilder(MacroCase::class)
                     ->disableOriginalConstructor()
                     ->setMethods(['glueUsingRules'])
                     ->getMock();

        // Setting `upperCase` property value
        $reflectionObject  = new ReflectionObject($mock);
        $titleCaseProperty = $reflectionObject->getProperty('upperCase');
        $titleCaseProperty->setAccessible(true);
        $titleCaseProperty->setValue($mock, 789);

        // Configuring stub
        $mock->expects($this->once())
             ->method('glueUsingRules')
             ->with(MacroCase::DELIMITER, 789)
             ->willReturn('e1bfd762321e409cee4ac0b6e841963c');

        /** @var \Jawira\CaseConverter\Glue\MacroCase $mock */
        $returned = $mock->glue();
        $this->assertSame('e1bfd762321e409cee4ac0b6e841963c', $returned, 'Returned value is not the expected');
    }
}
