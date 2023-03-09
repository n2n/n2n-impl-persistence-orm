<?php

namespace n2n\impl\persistence\orm\property\mock;


use n2n\persistence\orm\attribute\Embedded;

class EmbeddedContainerMock {

	public int $id;
	#[Embedded(columnPrefix: 'holeradio_')]
	public EmbeddableMock $embeddableMock;
}