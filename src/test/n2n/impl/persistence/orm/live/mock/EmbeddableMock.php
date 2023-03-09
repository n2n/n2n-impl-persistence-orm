<?php

namespace n2n\impl\persistence\orm\live\mock;


use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\attribute\ManyToOne;

class EmbeddableMock {

	public string $name;
	#[OneToMany(SimpleTargetMock::class, cascade: CascadeType::ALL)]
	#[JoinColumn('embeddable_mock_id')]
	public \ArrayObject $simpleTargetMocks;

	#[OneToMany(SimpleTargetMock::class, cascade: CascadeType::ALL)]
	public \ArrayObject $notSimpleTargetMocks;


	#[ManyToOne(cascade: CascadeType::ALL)]
	public ?SimpleTargetMock $verySimpleTargetMock = null;
}