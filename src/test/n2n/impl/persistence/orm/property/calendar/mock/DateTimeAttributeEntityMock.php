<?php

namespace n2n\impl\persistence\orm\property\calendar\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\persistence\orm\attribute\DateTime;

#[EntityListeners(LifecycleListener::class)]
class DateTimeAttributeEntityMock {
	#[Id(generated: false)]
	public int $id;

	#[DateTime]
	public \DateTime $mutableField;

	#[DateTime]
	public ?\DateTimeImmutable $immutableField = null;

	#[DateTime]
	public ?\DateTimeInterface $interfaceField = null;
}
