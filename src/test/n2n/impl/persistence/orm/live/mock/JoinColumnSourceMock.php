<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\ManyToOne;

class JoinColumnSourceMock {

	private int $id;
	#[ManyToOne]
	private ?JoinColumnTargetMock $targetMock = null;

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

	/**
	 * @return JoinColumnTargetMock|null
	 */
	public function getTargetMock(): ?JoinColumnTargetMock {
		return $this->targetMock;
	}

	/**
	 * @param JoinColumnTargetMock|null $targetMock
	 */
	public function setTargetMock(?JoinColumnTargetMock $targetMock): void {
		$this->targetMock = $targetMock;
	}

}