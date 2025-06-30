<?php

namespace n2n\impl\persistence\orm\property\relation\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\FetchType;


#[EntityListeners(LifecycleListener::class)]
class ToOneMandatoryEntityMock {
	#[Id(generated: false)]
	public int $id;

	#[ManyToOne(SimpleTargetMock::class, cascade: CascadeType::ALL, fetch: FetchType::EAGER)]
	public SimpleTargetMock $joinColumnTarget;

}