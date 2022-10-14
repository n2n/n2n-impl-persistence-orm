<?php
namespace n2n\impl\persistence\orm\property\mock;

use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\Id;

class TargetMock {
	#[OneToOne(EntityPropertiesMock::class)]
	public $oneToOne;

	#[Id]
	public $id;
}