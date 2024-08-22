<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Transient;
use n2n\context\attribute\ThreadScoped;
use n2n\persistence\orm\LifecycleEvent;

#[ThreadScoped]
class LifecycleListener {

	#[Transient]
	public array $prePersistNums = [];
	#[Transient]
	public array $postPersistNums = [];
	#[Transient]
	public array $preUpdateNums = [];
	#[Transient]
	public array $postUpdateNums = [];
	#[Transient]
	public array $preRemoveNums = [];
	#[Transient]
	public array $postRemoveNums = [];
	/**
	 * @var LifecycleEvent[][]
	 */
	#[Transient]
	public array $events = [];
	/**
	 * @var LifecycleEvent[]
	 */
	#[Transient]
	public array $allEvents = [];

	private function _prePersist(LifecycleEvent $event): void {
		$this->incr($this->prePersistNums, $event);
	}

	private function _postPersist(LifecycleEvent $event): void {
		$this->incr($this->postPersistNums, $event);
	}

	private function _preUpdate(LifecycleEvent $event): void {
		$this->incr($this->preUpdateNums, $event);
	}

	private function _postUpdate(LifecycleEvent $event): void {
		$this->incr($this->postUpdateNums, $event);
	}

	private function _preRemove(LifecycleEvent $event): void {
		$this->incr($this->preRemoveNums, $event);
	}

	private function _postRemove(LifecycleEvent $event): void {
		$this->incr($this->postRemoveNums, $event);
	}

	private function incr(array &$arr, LifecycleEvent $event): void {
		$entityObj = $event->getEntityObj();
		$className = get_class($entityObj);
		if (!isset($arr[$className])) {
			$arr[$className] = 0;
		}

		$arr[$className]++;

		if (!isset($this->events[$className])) {
			$this->events[$className] = [];
		}

		$this->events[$className][] = $event;
	}

	/**
	 * @return string[]
	 */
	function getClassNames(): array {
		return array_keys(array_merge($this->prePersistNums, $this->postPersistNums, $this->preUpdateNums,
				$this->postUpdateNums, $this->preRemoveNums, $this->postRemoveNums));
	}

	function getNum(): int {
		return array_sum($this->prePersistNums) + array_sum($this->postPersistNums) + array_sum($this->preUpdateNums)
				+ array_sum($this->postUpdateNums) +  array_sum($this->preRemoveNums)
				+ array_sum($this->postRemoveNums);
	}

}
