<?php

namespace n2n\impl\persistence\orm\property\mock;

use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\Id;

class NoTypeManyToOneMock {
	public $id;
	#[ManyToOne]
	private $manyToOne;

	public function getManyToOne() {
		return $this->manyToOne;
	}

	public function setManyToOne($manyToOne): void {
		$this->manyToOne = $manyToOne;
	}
}