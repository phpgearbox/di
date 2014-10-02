<?php
////////////////////////////////////////////////////////////////////////////////
// __________ __             ________                   __________              
// \______   \  |__ ______  /  _____/  ____ _____ ______\______   \ _______  ___
//  |     ___/  |  \\____ \/   \  ____/ __ \\__  \\_  __ \    |  _//  _ \  \/  /
//  |    |   |   Y  \  |_> >    \_\  \  ___/ / __ \|  | \/    |   (  <_> >    < 
//  |____|   |___|  /   __/ \______  /\___  >____  /__|  |______  /\____/__/\_ \
//                \/|__|           \/     \/     \/             \/            \/
// -----------------------------------------------------------------------------
//          Designed and Developed by Brad Jones <brad @="bjc.id.au" />         
// -----------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

use Mockery as m;
use Gears\Di\Container;
use Gears\Di\ServiceProviderInterface;

class DiContainerTest extends PHPUnit_Framework_TestCase
{
	public function testWithString()
	{
		$c = new Container();
		$c['param'] = 'value';
		
		$this->assertEquals('value', $c['param']);
	}

	public function testWithClosure()
	{
		$c = new Container();
		$c['service'] = function () { return m::mock('foo'); };

		$this->assertInstanceOf('foo', $c['service']);
	}

	public function testServicesShouldBeDifferent()
	{
		$c = new Container();
		$c['service'] = $c->factory(function () { return m::mock('foo'); });

		$serviceOne = $c['service'];
		$this->assertInstanceOf('foo', $serviceOne);

		$serviceTwo = $c['service'];
		$this->assertInstanceOf('foo', $serviceTwo);

		$this->assertNotSame($serviceOne, $serviceTwo);
	}

	/*public function testShouldPassContainerAsParameter()
	{
		$c = new Container();
		$c['service'] = function () {
			return new Fixtures\Service();
		};
		$c['container'] = function ($container) {
			return $container;
		};

		$this->assertNotSame($c, $c['service']);
		$this->assertSame($c, $c['container']);
	}

	public function testIsset()
	{
		$c = new Container();
		$c['param'] = 'value';
		$c['service'] = function () {
			return new Fixtures\Service();
		};

		$c['null'] = null;

		$this->assertTrue(isset($c['param']));
		$this->assertTrue(isset($c['service']));
		$this->assertTrue(isset($c['null']));
		$this->assertFalse(isset($c['non_existent']));
	}

	public function testConstructorInjection()
	{
		$params = array("param" => "value");
		$c = new Container($params);

		$this->assertSame($params['param'], $c['param']);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier "foo" is not defined.
	 *
	public function testOffsetGetValidatesKeyIsPresent()
	{
		$c = new Container();
		echo $c['foo'];
	}

	public function testOffsetGetHonorsNullValues()
	{
		$c = new Container();
		$c['foo'] = null;
		$this->assertNull($c['foo']);
	}

	public function testUnset()
	{
		$c = new Container();
		$c['param'] = 'value';
		$c['service'] = function () {
			return new Fixtures\Service();
		};

		unset($c['param'], $c['service']);
		$this->assertFalse(isset($c['param']));
		$this->assertFalse(isset($c['service']));
	}

	/**
	 * @dataProvider serviceDefinitionProvider
	 *
	public function testShare($service)
	{
		$c = new Container();
		$c['shared_service'] = $service;

		$serviceOne = $c['shared_service'];
		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceOne);

		$serviceTwo = $c['shared_service'];
		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceTwo);

		$this->assertSame($serviceOne, $serviceTwo);
	}

	/**
	 * @dataProvider serviceDefinitionProvider
	 *
	public function testProtect($service)
	{
		$c = new Container();
		$c['protected'] = $c->protect($service);

		$this->assertSame($service, $c['protected']);
	}

	public function testGlobalFunctionNameAsParameterValue()
	{
		$c = new Container();
		$c['global_function'] = 'strlen';
		$this->assertSame('strlen', $c['global_function']);
	}

	public function testRaw()
	{
		$c = new Container();
		$c['service'] = $definition = $c->factory(function () { return 'foo'; });
		$this->assertSame($definition, $c->raw('service'));
	}

	public function testRawHonorsNullValues()
	{
		$c = new Container();
		$c['foo'] = null;
		$this->assertNull($c->raw('foo'));
	}

	public function testFluentRegister()
	{
		$c = new Container;
		$this->assertSame($c, $c->register($this->getMock('Pimple\ServiceProviderInterface')));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier "foo" is not defined.
	 *
	public function testRawValidatesKeyIsPresent()
	{
		$c = new Container();
		$c->raw('foo');
	}

	/**
	 * @dataProvider serviceDefinitionProvider
	 *
	public function testExtend($service)
	{
		$c = new Container();
		$c['shared_service'] = function () {
			return new Fixtures\Service();
		};
		$c['factory_service'] = $c->factory(function () {
			return new Fixtures\Service();
		});

		$c->extend('shared_service', $service);
		$serviceOne = $c['shared_service'];
		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceOne);
		$serviceTwo = $c['shared_service'];
		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceTwo);
		$this->assertSame($serviceOne, $serviceTwo);
		$this->assertSame($serviceOne->value, $serviceTwo->value);

		$c->extend('factory_service', $service);
		$serviceOne = $c['factory_service'];
		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceOne);
		$serviceTwo = $c['factory_service'];
		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $serviceTwo);
		$this->assertNotSame($serviceOne, $serviceTwo);
		$this->assertNotSame($serviceOne->value, $serviceTwo->value);
	}

	public function testExtendDoesNotLeakWithFactories()
	{
		if (extension_loaded('pimple')) {
			$this->markTestSkipped('Pimple extension does not support this test');
		}
		$c = new Container();

		$c['foo'] = $c->factory(function () { return; });
		$c['foo'] = $c->extend('foo', function ($foo, $c) { return; });
		unset($c['foo']);

		$p = new \ReflectionProperty($c, 'values');
		$p->setAccessible(true);
		$this->assertEmpty($p->getValue($c));

		$p = new \ReflectionProperty($c, 'factories');
		$p->setAccessible(true);
		$this->assertCount(0, $p->getValue($c));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier "foo" is not defined.
	 *
	public function testExtendValidatesKeyIsPresent()
	{
		$c = new Container();
		$c->extend('foo', function () {});
	}

	public function testKeys()
	{
		$c = new Container();
		$c['foo'] = 123;
		$c['bar'] = 123;

		$this->assertEquals(array('foo', 'bar'), $c->keys());
	}

	/** @test *
	public function settingAnInvokableObjectShouldTreatItAsFactory()
	{
		$c = new Container();
		$c['invokable'] = new Fixtures\Invokable();

		$this->assertInstanceOf('Pimple\Tests\Fixtures\Service', $c['invokable']);
	}

	/** @test *
	public function settingNonInvokableObjectShouldTreatItAsParameter()
	{
		$c = new Container();
		$c['non_invokable'] = new Fixtures\NonInvokable();

		$this->assertInstanceOf('Pimple\Tests\Fixtures\NonInvokable', $c['non_invokable']);
	}

	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Service definition is not a Closure or invokable object.
	 *
	public function testFactoryFailsForInvalidServiceDefinitions($service)
	{
		$c = new Container();
		$c->factory($service);
	}

	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Callable is not a Closure or invokable object.
	 *
	public function testProtectFailsForInvalidServiceDefinitions($service)
	{
		$c = new Container();
		$c->protect($service);
	}

	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier "foo" does not contain an object definition.
	 *
	public function testExtendFailsForKeysNotContainingServiceDefinitions($service)
	{
		$c = new Container();
		$c['foo'] = $service;
		$c->extend('foo', function () {});
	}

	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Extension service definition is not a Closure or invokable object.
	 *
	public function testExtendFailsForInvalidServiceDefinitions($service)
	{
		$c = new Container();
		$c['foo'] = function () {};
		$c->extend('foo', $service);
	}

	/**
	 * Provider for invalid service definitions
	 *
	public function badServiceDefinitionProvider()
	{
		return array(
		  array(123),
		  array(new Fixtures\NonInvokable())
		);
	}

	/**
	 * Provider for service definitions
	 *
	public function serviceDefinitionProvider()
	{
		return array(
			array(function ($value) {
				$service = new Fixtures\Service();
				$service->value = $value;

				return $service;
			}),
			array(new Fixtures\Invokable())
		);
	}

	public function testDefiningNewServiceAfterFreeze()
	{
		$c = new Container();
		$c['foo'] = function () {
			return 'foo';
		};
		$foo = $c['foo'];

		$c['bar'] = function () {
			return 'bar';
		};
		$this->assertSame('bar', $c['bar']);
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Cannot override frozen service "foo".
	 *
	public function testOverridingServiceAfterFreeze()
	{
		$c = new Container();
		$c['foo'] = function () {
			return 'foo';
		};
		$foo = $c['foo'];

		$c['foo'] = function () {
			return 'bar';
		};
	}

	public function testRemovingServiceAfterFreeze()
	{
		$c = new Container();
		$c['foo'] = function () {
			return 'foo';
		};
		$foo = $c['foo'];

		unset($c['foo']);
		$c['foo'] = function () {
			return 'bar';
		};
		$this->assertSame('bar', $c['foo']);
	}

	public function testExtendingService()
	{
		$c = new Container();
		$c['foo'] = function () {
			return 'foo';
		};
		$c['foo'] = $c->extend('foo', function ($foo, $app) {
			return "$foo.bar";
		});
		$c['foo'] = $c->extend('foo', function ($foo, $app) {
			return "$foo.baz";
		});
		$this->assertSame('foo.bar.baz', $c['foo']);
	}

	public function testExtendingServiceAfterOtherServiceFreeze()
	{
		$c = new Container();
		$c['foo'] = function () {
			return 'foo';
		};
		$c['bar'] = function () {
			return 'bar';
		};
		$foo = $c['foo'];

		$c['bar'] = $c->extend('bar', function ($bar, $app) {
			return "$bar.baz";
		});
		$this->assertSame('bar.baz', $c['bar']);
	}*/

	protected function tearDown()
	{
		m::close();
	}
}