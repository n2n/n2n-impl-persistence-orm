<?php

namespace n2n\impl\persistence\orm\live\mock;


use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\ManyToMany;
use n2n\persistence\orm\FetchType;

class EmbeddableMock {

	public string $name;

	#[OneToMany(SimpleTargetMock::class, cascade: CascadeType::ALL, fetch: FetchType::EAGER)]
	#[JoinColumn('embeddable_mock_id')]
	public \ArrayObject $simpleTargetMocks;

	#[OneToMany(SimpleTargetMock::class, cascade: CascadeType::ALL, fetch: FetchType::EAGER)]
	public \ArrayObject $notSimpleTargetMocks;

	#[ManyToOne(cascade: CascadeType::ALL, fetch: FetchType::EAGER)]
	public ?SimpleTargetMock $verySimpleTargetMock = null;

	#[ManyToMany(SimpleTargetMock::class, cascade: CascadeType::ALL, fetch: FetchType::EAGER)]
	public \ArrayObject $manySimpleTargetMocks;
}