<?php

namespace n2n\impl\persistence\orm\property\calendar\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\util\calendar\Time;

#[EntityListeners(LifecycleListener::class)]
class TimeEntityMock {
	#[Id(generated: false)]
	public int $id;
	public Time $firstTime;
	public ?Time $secondTime = null;


}