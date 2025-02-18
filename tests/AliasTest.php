<?php
namespace AliasCraft\Tests;

use AliasCraft\Alias;
use PHPUnit\Framework\TestCase;

class AliasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any previously registered aliases for test isolation.
        $reflection = new \ReflectionClass(Alias::class);
        $prop       = $reflection->getProperty('aliases');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    public function testRegisterAndRunAlias()
    {
        Alias::register('greet', function ($name) {
            return "Hello, $name!";
        }, ['args' => ['name']]);

        $result = Alias::run('greet', 'Alice');
        $this->assertEquals("Hello, Alice!", $result);
    }

    public function testPreAndPostHooks()
    {
        $hookCalled = false;
        Alias::registerPreHook(function ($alias, &$args) use (&$hookCalled) {
            $hookCalled = true;
        });
        Alias::register('test', function () {
            return 'test';
        });
        Alias::run('test');
        $this->assertTrue($hookCalled);
    }
}
