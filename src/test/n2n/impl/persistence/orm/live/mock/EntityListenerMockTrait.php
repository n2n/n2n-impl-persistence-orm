<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Transient;
use n2n\persistence\orm\LifecycleEvent;
use nql\bo\Event;

trait EntityListenerMockTrait {

	#[Transient]
	private array $methodCalls = [];
	/**
	 * @var LifecycleEvent[] $events
	 */
	#[Transient]
	private array $events = [];

	private function _postLoad(LifecycleEvent $e): void {
		$this->methodCalls[] = '_postLoad';
		$this->events[] = $e;
	}

	private function _prePersist(LifecycleEvent $e): void {
		$this->methodCalls[] = '_prePersist';
		$this->events[] = $e;
	}

	private function _postPersist(LifecycleEvent $e): void {
		$this->methodCalls[] = '_postPersist';
		$this->events[] = $e;
	}

	private function _preUpdate(LifecycleEvent $e): void {
		$this->methodCalls[] = '_preUpdate';
		$this->events[] = $e;
	}

	private function _postUpdate(LifecycleEvent $e): void {
		$this->methodCalls[] = '_postUpdate';
		$this->events[] = $e;
	}

	private function _preRemove(LifecycleEvent $e): void {
		$this->methodCalls[] = '_preRemove';
		$this->events[] = $e;
	}

	private function _postRemove(LifecycleEvent $e): void {
		$this->methodCalls[] = '_postRemove';
		$this->events[] = $e;
	}

	function getMethodCalls(): array {
		return $this->methodCalls;
	}

	/**
	 * @return LifecycleEvent[]
	 */
	function getEvents(): array {
		return $this->events;
	}
}