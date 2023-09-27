<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\ManyToOne;

class ObservableTestObj {
	use EntityListenerMockTrait;

	#[Id(generated: false)]
	private int $id;

	#[OneToMany(ObservableTargetTestObj::class, cascade: CascadeType::ALL)]
	#[JoinColumn(name: 'observable_test_obj_id')]
	private \ArrayObject $observableTargetTestObjs;

	#[ManyToOne(ObservableTargetTestObj::class, cascade: CascadeType::ALL)]
	private ?ObservableTargetTestObj $observableTargetTestObj = null;

	function __construct(int $id) {
		$this->observableTargetTestObjs = new \ArrayObject();
		$this->id = $id;
	}

	function getId(): int {
		return $this->id;
	}



	function getObservableTargetTestObjs(): \ArrayObject {
		return $this->observableTargetTestObjs;
	}

	function setObservableTargetTestObjs(\ArrayObject $observableTargetTestObjs): void {
		$this->observableTargetTestObjs = $observableTargetTestObjs;
	}

	public function getObservableTargetTestObj(): ?ObservableTargetTestObj {
		return $this->observableTargetTestObj;
	}

	public function setObservableTargetTestObj(?ObservableTargetTestObj $observableTargetTestObj): void {
		$this->observableTargetTestObj = $observableTargetTestObj;
	}

}
