<?php namespace Gears\Di;
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

use Closure;
use ArrayAccess;
use SplObjectStorage;
use ReflectionProperty;
use ReflectionException;
use RuntimeException;

class Container implements ArrayAccess
{
	/**
	 * Property: reflector
	 * =========================================================================
	 * In some cases we can save some memory by saving an instance
	 * of ```ReflectionProperty``` and reusing it later on.
	 */
	protected $reflector;

	/**
	 * Property: factories
	 * =========================================================================
	 * This will contain a list of closures in the container that we want to
	 * protect from being replaced by their output. We do this by using a
	 * ```SplObjectStorage``` object.
	 */
	protected $factories;

	/**
	 * Property: protected
	 * =========================================================================
	 * This will contain a list of closures in the container that we want to
	 * protect from being resolved. They will be treated like any other
	 * primitive type. We do this by using a ```SplObjectStorage``` object.
	 */
	protected $protected;

	/**
	 * Property: frozen
	 * =========================================================================
	 * When a service has been resolved. ie: Someone either inside or outside
	 * of the container has access the service we mark it as frozen.
	 * This is so that it can not be over ridden.
	 */
	protected $frozen = [];

	/**
	 * Method: __construct
	 * =========================================================================
	 * Lets construct the container.
	 * 
	 * All we do here is set some defaults and inject any custom services.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $config: An array of values to inject into the container.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function __construct($config = [])
	{
		// Initialise some object storage
		$this->factories = new SplObjectStorage();
		$this->protected = new SplObjectStorage();

		// Set some defaults
		if (method_exists($this, 'setDefaults'))
		{
			$this->setDefaults();
		}

		// Loop through our injected config array and set custom values.
		foreach ($config as $key => $value)
		{
			$this[$key] = $value;
		}
	}

	/**
	 * Method: offsetExists
	 * =========================================================================
	 * Implements ```ArrayAccess```.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $offset: The offset to check to see if it exists.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * bool
	 */
	public function offsetExists($offset)
	{
		// All injectable properties must be prefixed with 'inject'
		$offset = 'inject'.ucfirst($offset);

		// Lets see if the property exists
		try
		{
			// Save the reflector so that we don't have to create new ones.
			$this->reflector = new ReflectionProperty($this, $offset);
		}
		catch (ReflectionException $e)
		{
			return false;
		}

		// Regardless of if the property is prefixed with 'inject'
		// we want to make sure private stuff stays private.
		if (!$this->reflector->isPrivate())
		{
			// After we return true here, we will most likely need to
			// access this property. So may as well do it once here.
			$this->reflector->setAccessible(true);
			return true;
		}
		else
		{
			// Bail Out
			throw new RuntimeException('You cant touch my privates!!!');
		}
	}

	/**
	 * Method: offsetGet
	 * =========================================================================
	 * Implements ```ArrayAccess```.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $offset: The offset to get out of the container.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * mixed
	 */
	public function offsetGet ($offset)
	{
		// Make sure the property exists
		if ($this->offsetExists($offset))
		{
			// Grab its current value
			$value = $this->reflector->getValue($this);

			// If the value is a closure we need to resolve it.
			if ($value instanceof Closure)
			{
				// Rebind the closure to the container.
				// This allows the closure to use the $this var.
				$bound = Closure::bind($value, $this, '\Gears\Di\Container');

				// If its not a factory and it's not protected we can resolve it
				if (!$this->factories->contains($value) && !$this->protected->contains($value))
				{
					// Save the current reflector as calling
					// the closure could be recursive.
					$r = $this->reflector;

					// Run the closure
					$output = $bound();

					// Save it for later use
					$r->setValue($this, $output);

					// Once a service has been resolved thats it no more
					// changes as other services now depend on this.
					$this->frozen[$offset] = true;
				}
				elseif ($this->factories->contains($value))
				{
					// Run the factory but don't save it's output
					$output = $bound();
				}
				else
				{
					// It must be a protected closure
					$output = $bound;
				}
			}
			else
			{
				// The value has already been resolved
				// or it just a primitive type.
				$output = $value;
			}

			return $output;
		}
		else
		{
			throw new RuntimeException('Container does not contain: '.$offset);
		}
	}

	/**
	 * Method: offsetSet
	 * =========================================================================
	 * Implements ```ArrayAccess```.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $offset: The offset to set in the container.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function offsetSet($offset, $value)
	{
		if (isset($this->frozen[$offset]))
		{
			throw new RuntimeException('Cannot override frozen service: '.$offset);
		}

		if ($this->offsetExists($offset))
		{
			$this->reflector->setValue($this, $value);
		}
		else
		{
			$this->{'inject'.ucfirst($offset)} = $value;
		}
	}

	/**
	 * Method: offsetUnset
	 * =========================================================================
	 * Implements ```ArrayAccess```.
	 * 
	 * Not implemented on purpose.
	 * Once a dependency has been injected it can not be un-injected.
	 * You have to replace it with something new that will do the job.
	 * Or ummm... mock the job if Unit Testing :)
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $offset: does nothing
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function offsetUnset($offset)
	{		
		throw new RuntimeException('You can not unset a dependency!');
	}

	/**
	 * Method: __get
	 * =========================================================================
	 * PHP Magic Method, provides object syntax to resolve services.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $name: The name of service to resolve out of the container.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * mixed
	 */
	public function __get($name)
	{
		return $this->offsetGet($name);
	}

	/**
	 * Method: __set
	 * =========================================================================
	 * PHP Magic Method, provides object syntax to inject services.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $name: The name of the service|attribute to inject into the container.
	 * - $value: A closure if defining a service, otherwise any primitive type.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public function __set($name, $value)
	{
		if (substr($name, 0, 6) == 'inject')
		{
			$this->{$name} = $value;
		}
		else
		{
			$this->offsetSet($name, $value);
		}
	}

	/**
	 * Method: __call
	 * =========================================================================
	 * PHP Magic Method, provides object syntax to resolve protected services.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 *  - $name: The name of service to resolve out of the container.
	 *  - $args: The arguments to pass to protected service.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * mixed
	 */
	public function __call($name, $args)
	{
		// Grab the service
		$value = $this->offsetGet($name);

		// If its a protected closure lets run it
		if ($value instanceof Closure)
		{
			return call_user_func_array($value, $args);
		}

		// Otherwise we will just return the value
		return $value;
	}

	/**
	 * Method: factory
	 * =========================================================================
	 * By default the container will resolve a service and save a copy for
	 * any later requests. However if your service needs to be re-initialised
	 * each time it is requested. Simply wrap it up in this method.
	 * 
	 * For example:
	 * 
	 * ```php
	 * $c = new Container();
	 * $c->fooBarService = $c->factory(function(){ return new FooBar(); });
	 * ```
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $callable: The callable to define as a factory.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * Closure
	 */
	public function factory(Closure $callable)
	{
		$this->factories->attach($callable);
		return $callable;
	}

	/**
	 * Method: protect
	 * =========================================================================
	 * By default the container will resolve a service and save a copy for
	 * any later requests. However if your service needs to be re-initialised
	 * each time it is requested. Simply wrap it up in this method.
	 * 
	 * For example:
	 * 
	 * ```php
	 * $c = new Container();
	 * $c->protectedFunc = $c->protect(function($a, $b){ return $a+$b; });
	 * ```
	 * 
	 * > NOTE: That the Closure will not be bound to the container.
	 * > ie: $this is not available.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $callable: The callable to define as protected.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * Closure
	 */
	public function protect(Closure $callable)
	{
		$this->protected->attach($callable);
		return $callable;
	}

	/**
	 * Method: register
	 * =========================================================================
	 * This will register a Service Provider into the Container.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * - $provider: An instance of ```ServiceProviderInterface```.
	 * - $values: An array of values that customizes the provider.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * Closure
	 */
	public function register(ServiceProviderInterface $provider, array $values = array())
	{
		$provider->register($this);

		foreach ($values as $key => $value)
		{
			$this[$key] = $value;
		}

		return $this;
	}
}