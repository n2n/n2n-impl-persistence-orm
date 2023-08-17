<?php

namespace n2n\impl\persistence\orm\live\mock;

class JoinColumnTargetMock {
	private int $id;

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId(int $id): void {
		$this->id = $id;
	}


}