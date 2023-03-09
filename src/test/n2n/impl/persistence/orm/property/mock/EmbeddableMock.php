<?php

namespace n2n\impl\persistence\orm\property\mock;


use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\JoinColumn;

class EmbeddableMock {

	public string $name;
	#[OneToMany(SimpleTargetMock::class)]
	#[JoinColumn('embeddable_mock_id')]
	public \ArrayObject $simpleTargetMocks;
}