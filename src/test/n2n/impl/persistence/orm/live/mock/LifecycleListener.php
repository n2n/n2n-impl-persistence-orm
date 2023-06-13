<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Transient;
use n2n\context\attribute\ThreadScoped;

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

	private function _prePersist($entityObj): void {
		$this->incr($this->prePersistNums, $entityObj);
	}

	private function _postPersist($entityObj): void {
		$this->incr($this->postPersistNums, $entityObj);
	}

	private function _preUpdate($entityObj): void {
		$this->incr($this->preUpdateNums, $entityObj);
	}

	private function _postUpdate($entityObj): void {
		$this->incr($this->postUpdateNums, $entityObj);
	}

	private function _preRemove($entityObj): void {
		$this->incr($this->preRemoveNums, $entityObj);
	}

	private function _postRemove($entityObj): void {
		$this->incr($this->postRemoveNums, $entityObj);
	}

	private function incr(array &$arr, $entityObj): void {
		$className = get_class($entityObj);
		if (!isset($arr[$className])) {
			$arr[$className] = 0;
		}

		$arr[$className]++;
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