<?php
namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\attribute\JoinColumn;

class LazyContainerMock {

	#[Id(generated: false)]
	private int $id;
	private ?string $holeradio = null;

	#[OneToMany(SimpleTargetMock::class, cascade: CascadeType::ALL)]
	#[JoinColumn('lazy_container_mock_id')]
	private \ArrayObject $simpleTargetMocks;

	function __construct(int $id) {
		$this->id = $id;
		$this->simpleTargetMocks = new \ArrayObject();
	}

	function getId(): int {
		return $this->id;
	}

	function getSimpleTargetMocks(): \ArrayObject {
		return $this->simpleTargetMocks;
	}

	function setSimpleTargetMocks(\ArrayObject $simpleTargetMocks): void {
		$this->simpleTargetMocks = $simpleTargetMocks;
	}
}