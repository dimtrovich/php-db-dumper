<?php

namespace Dimtrovich\DbDumper;

class Event
{
	/**
	 * @var array<string, callable[]>
	 */
	private array $listeners = [];


	/**
	 * Register a listener for event
	 */
	public function on(string $event, callable $callable): void
	{
		if (! array_key_exists($event, $this->listeners)) {
			$this->listeners[$event] = [];
		}

		$this->listeners[$event][] = $callable;
	}

	/**
	 * Trigger event
	 */
	public function emit(string $event, ...$args)
	{
		$listeners = $this->listeners[$event] ?? [];

		foreach ($listeners as $callable) {
			call_user_func_array($callable, $args);
		}
	}
}
