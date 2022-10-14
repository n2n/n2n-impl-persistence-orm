<?php

namespace n2n\impl\persistence\orm\property\mock;

use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\Id;

class TargetEntityNotFoundMock {
	public $id;
	#[ManyToOne]
	private Test $manyToOne;

	public function getManyToOne() {
		return $this->manyToOne;
	}

	public function setManyToOne($manyToOne): void {
		$this->manyToOne = $manyToOne;
	}
}