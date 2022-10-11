<?php
namespace n2n\impl\persistence\orm\property\test;

use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\Id;

class TargetClassTest {
	#[OneToOne(PersistenceTestClass::class)]
	public $oneToOne;

	public $id;
}