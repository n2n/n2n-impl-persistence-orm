<?php

namespace n2n\impl\persistence\orm\property\embedded\mock;

use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\Embedded;
use n2n\impl\persistence\orm\live\mock\EmbeddableMock;

class EmbeddedContainerManMock {

	#[Id(generated: false)]
	public int $id;

	#[Embedded(columnPrefix: 'mandatory_')]
	public EmbeddableManMock $mandatoryEmbeddableMock;
	#[Embedded(columnPrefix: 'optional_')]
	public ?EmbeddableManMock $optionalEmbeddableMock;
}