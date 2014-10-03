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

	public function testShouldPassContainerAsParameter()
	{
		$c = new Container();
		$c['service'] = function () { return m::mock('foo'); };
		$c['container'] = function () { return $this; };

		$this->assertNotSame($c, $c['service']);
		$this->assertSame($c, $c['container']);
	}

	public function testIsset()
	{
		$c = new Container();
		$c['param'] = 'value';
		$c['service'] = function () { return m::mock('foo'); };
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
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Container does not contain: foo
	 */
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

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage You can not unset a dependency!
	 */
	public function testUnset()
	{
		$c = new Container();
		$c['param'] = 'value';
		unset($c['param']);
	}

	public function testShare()
	{
		$c = new Container();
		$c['shared_service'] = function () { return m::mock('foo'); };

		$serviceOne = $c['shared_service'];
		$this->assertInstanceOf('foo', $serviceOne);

		$serviceTwo = $c['shared_service'];
		$this->assertInstanceOf('foo', $serviceTwo);

		$this->assertSame($serviceOne, $serviceTwo);
	}

	public function testProtect()
	{
		$c = new Container();
		$service1 = function () { return m::mock('foo'); };
		$service2 = function () { return m::mock('foo'); };
		$c['unprotected'] = $service1;
		$c['protected'] = $c->protect($service2);

		$this->assertInstanceOf('foo', $c['unprotected']);
		$this->assertInstanceOf('Closure', $c['protected']);
	}

	public function testGlobalFunctionNameAsParameterValue()
	{
		$c = new Container();
		$c['global_function'] = 'strlen';
		$this->assertSame('strlen', $c['global_function']);
	}

	public function testFluentRegister()
	{
		$c = new Container;
		$this->assertSame($c, $c->register($this->getMock('Gears\Di\ServiceProviderInterface')));
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testFactoryFailsForInvalidServiceDefinitions()
	{
		$c = new Container();
		$c->factory('foo');
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testProtectFailsForInvalidServiceDefinitions($service)
	{
		$c = new Container();
		$c->protect('foo');
	}

	public function testDefiningNewServiceAfterFreeze()
	{
		$c = new Container();
		$c['foo'] = function () { return 'foo'; };
		$foo = $c['foo'];
		$c['bar'] = function () { return 'bar'; };
		$this->assertSame('bar', $c['bar']);
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Cannot override frozen service: foo
	 */
	public function testOverridingServiceAfterFreeze()
	{
		$c = new Container();
		$c['foo'] = function () { return 'foo'; };
		$foo = $c['foo'];
		$c['foo'] = function () { return 'bar'; };
	}

	public function testObjectSyntax()
	{
		$c = new Container();
		$c->param = 'value';
		$c->service = function () { return m::mock('foo'); };

		$this->assertEquals('value', $c->param);
		$this->assertInstanceOf('foo', $c->service);
	}

	public function testThisIsBound()
	{
		$c = new Container();
		$c->param = 'value';
		$c->service = function () { return m::mock($this->param); };

		$this->assertInstanceOf('value', $c->service);
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Container does not contain: injectFoo
	 */
	public function testPrivateIsPrivate()
	{
		$c = new ExtendedContainer();
		$c->service = function () { return m::mock($this->injectFoo); };
		$c->service;
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage You cant touch my privates!!!
	 */
	public function testPrivateIsPrivateShortName()
	{
		$c = new ExtendedContainer();
		$c->service = function () { return m::mock($this->foo); };
		$c->service;
	}

	public function testProtectedNonInjectableGet()
	{
		$c = new ExtendedContainer();
		$c->service = function () { return m::mock($this->bar); };

		$this->assertInstanceOf('abc', $c->service);
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Container does not contain: bar
	 */
	public function testProtectedNonInjectableGetFromOutside()
	{
		$c = new ExtendedContainer();
		$c->bar;
	}

	public function testProtectedNonInjectableSet()
	{
		$c = new ExtendedContainer();
		$c->service = function (){ $this->bar = 'xyz'; return m::mock($this->bar); };

		$this->assertInstanceOf('xyz', $c->service);
	}

	
	public function testProtectedNonInjectableSetFromOutside()
	{
		$c = new ExtendedContainer();
		$c->bar = 'xyz';

		$this->assertEquals('xyz', $c->injectBar);
	}

	public function testProtectedInjectable()
	{
		$c = new ExtendedContainer();
		$c->service = function () { return m::mock($this->baz); };

		$this->assertInstanceOf('baz', $c->service);
		$this->assertEquals('baz', $c->baz);
	}

	public function testSetDefaults()
	{
		$c = new DefaultContainer();

		$this->assertInstanceOf('FooService', $c->foo);
		$this->assertInstanceOf('BarService', $c->bar);
		$this->assertInstanceOf('FooService', $c->bar->test());
		$this->assertInstanceOf('FooService', $c->test());

		$c = new DefaultContainer();
		$c->foo = function() { return m::mock('BazService'); };

		$this->assertInstanceOf('BazService', $c->foo);
		$this->assertInstanceOf('BarService', $c->bar);
		$this->assertInstanceOf('BazService', $c->bar->test());
		$this->assertInstanceOf('BazService', $c->test());

		$c = new DefaultContainer(['foo' => function() { return m::mock('BazService'); }]);

		$this->assertInstanceOf('BazService', $c->foo);
		$this->assertInstanceOf('BarService', $c->bar);
		$this->assertInstanceOf('BazService', $c->bar->test());
		$this->assertInstanceOf('BazService', $c->test());
	}

	protected function tearDown()
	{
		m::close();
	}
}

class ExtendedContainer extends Container
{
	private $injectFoo = '123';

	protected $bar = 'abc';

	protected $injectBaz = 'baz';
}

class DefaultContainer extends Container
{
	protected $injectFoo;

	protected $injectBar;

	protected function setDefaults()
	{
		$this->foo = function() { return m::mock('FooService'); };

		$this->bar = function() { return m::mock('BarService', ['test' => $this->foo]); };
	}

	public function test()
	{
		return $this->foo;
	}
}