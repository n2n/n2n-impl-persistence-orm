<?php

namespace n2n\impl\persistence\orm\live\mock;


use n2n\persistence\orm\attribute\Embedded;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;

#[EntityListeners(LifecycleListener::class)]
class EmbeddedContainerMock {

	#[Id(generated: false)]
	public int $id;

	#[Embedded(columnPrefix: 'holeradio_')]
	public EmbeddableMock $embeddableMock;

}