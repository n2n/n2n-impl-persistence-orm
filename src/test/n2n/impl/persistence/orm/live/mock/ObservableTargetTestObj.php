<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;

#[EntityListeners(OttoEntityListenerMock::class)]
class ObservableTargetTestObj {
	use EntityListenerMockTrait;

	#[Id(generated: false)]
	private int $id;

	function __construct(int $id) {
		$this->id = $id;
	}

	private ?string $holeradio = null;

	public function getId(): int {
		return $this->id;
	}

	public function getHoleradio(): ?string {
		return $this->holeradio;
	}

	public function setHoleradio(?string $holeradio): static {
		$this->holeradio = $holeradio;
		return $this;
	}

}
