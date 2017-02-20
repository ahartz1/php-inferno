<?php

function start(callable $first_function, array $args=[])
{
	$monad = new MaybeMonad();
	$monad->andThen($first_function, $args);
	return $monad;
}

/**
* Surprise! You're implementing a form of monads, although not super statically
* typed, it has some of the same saftey principles.
*/
class MaybeMonad
{
	private $callables = [];

	public function andThen(callable $next_function, array $extra_params=[])
	{
		$this->callables[] = array(
			'function' => $next_function,
			'params'   => $extra_params,
			);

		return $this;
	}
	
	public function resolve()
	{
		$result = null;
		foreach($this->callables as $callable) {
			if (!is_null($result)) {
				array_unshift($callable['params'], $result);
			}
			$result = call_user_func_array($callable['function'], $callable['params']);
			if (is_null($result)) {
				throw new UnexpectedValueException();
			}
		}

		return $result;
	}
}

/**
* This is a utility function provided free of charge. It is
* an example of function currying - like the factory pattern but for functions.
*
* Partial functions are functions that already have some arguments filled in. partial_right
* lets you fill in the right-most arguments for a function first. As an example:
* 
* function subtract($left_number, $right_number)
* {
* 	return $left_number - $right_number;
* }
*
* $less_5 = partial_right('subtract_left', 5);
* $less_5(11); // returns 6
*
* $always_100 = partial_right('subtract_left', 200, 100);
* $always_100(); // returns 100
*
* @param callable $callable the function to reference
* @param array $arguments the arguments to fix on the right
* side (from left-to-right)
*/
function partial_right(callable $callable, array $arguments)
{
	return function() use ($callable, $arguments)
	{
		return call_user_func_array($callable, array_merge(func_get_args(), $arguments));
	};
}

class MaybeComprehension
{
	private $target_object;
	private $monad;
	
	public function __construct($target_object)
	{
		$this->target_object = $target_object;
		$this->monad = new MaybeMonad();
	}
	
	/**
	* Virgil says: The danger of using magic methods as a proxy is when
	* you actually need a utility method on the proxy. In this case, we don't
	* want to name a method on the MaybeComprehension that could mask a method
	* on whatever $target_object ends up being. "resolve()" is a very common
	* method name, so it's possible the target and the comprehension collide. 
	*/
	public function _maybe_resolve()
	{
		return $this->monad->resolve();
	}
	
	public function __call($name, $args)
	{
		$this->monad = $this->monad->andThen([$this->target_object, $name], $args);
		return $this;
	}
}
