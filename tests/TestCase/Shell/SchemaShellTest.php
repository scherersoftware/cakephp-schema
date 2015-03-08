<?php
namespace Schema\Test\TestCase\Shell;

use Cake\TestSuite\TestCase;
use Schema\Shell\SchemaShell;

/**
 * Schema\Shell\SchemaShell Test Case
 */
class SchemaShellTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMock('Cake\Console\ConsoleIo');
        $this->Schema = new SchemaShell($this->io);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Schema);

        parent::tearDown();
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
