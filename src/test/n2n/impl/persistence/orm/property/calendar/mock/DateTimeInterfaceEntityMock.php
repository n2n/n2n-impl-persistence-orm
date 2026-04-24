<?php

namespace n2n\impl\persistence\orm\property\calendar\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;

#[EntityListeners(LifecycleListener::class)]
class DateTimeInterfaceEntityMock {
	#[Id(generated: false)]
	public int $id;
	
	public \DateTime $mutableField;
	public \DateTimeImmutable $immutableField;
	public \DateTimeInterface $interfaceField;
	public ?\DateTime $nullableMutable = null;
	public ?\DateTimeImmutable $nullableImmutable = null;
	public ?\DateTimeInterface $nullableInterface = null;
}
