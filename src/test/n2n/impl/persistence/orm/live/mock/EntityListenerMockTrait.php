<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Transient;

trait EntityListenerMockTrait {

	#[Transient]
	private array $methodCalls = [];

	private function _postLoad(): void {
		$this->methodCalls[] = '_postLoad';
	}

	private function _prePersist(): void {
		$this->methodCalls[] = '_prePersist';
	}

	private function _postPersist(): void {
		$this->methodCalls[] = '_postPersist';
	}

	private function _preUpdate(): void {
		$this->methodCalls[] = '_preUpdate';
	}

	private function _postUpdate(): void {
		$this->methodCalls[] = '_postUpdate';
	}

	private function _preRemove(): void {
		$this->methodCalls[] = '_preRemove';
	}

	private function _postRemove(): void {
		$this->methodCalls[] = '_postRemove';
	}

	function getMethodCalls(): array {
		return $this->methodCalls;
	}
}