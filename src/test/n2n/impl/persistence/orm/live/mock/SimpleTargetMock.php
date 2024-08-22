<?php
namespace n2n\impl\persistence\orm\live\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;

#[EntityListeners(LifecycleListener::class)]
class SimpleTargetMock {
	#[Id(generated: false)]
	public int $id;
	public string $holeradio;
}