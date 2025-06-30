<?php

namespace n2n\impl\persistence\orm\property\relation\mock;

use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\FetchType;

class ToOneWrapperEntityMock {
	#[Id(generated: false)]
	public int $id;
	#[ManyToOne(ToOneMandatoryEntityMock::class, fetch: FetchType::EAGER)]
	public ?ToOneMandatoryEntityMock $toOne = null;
}