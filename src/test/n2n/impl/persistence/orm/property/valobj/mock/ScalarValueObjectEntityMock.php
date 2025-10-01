<?php

namespace n2n\impl\persistence\orm\property\valobj\mock;

use n2n\persistence\orm\model\EntityModelManager;
use n2n\impl\persistence\orm\property\mock\EntityPropertiesMock;
use n2n\impl\persistence\orm\property\mock\TargetMock;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;

#[EntityListeners(LifecycleListener::class)]
class ScalarValueObjectEntityMock {
	#[Id(generated: false)]
	public PositiveInt $id;
	public PositiveInt $positiveInt;
	public ?ShortString $shortString = null;


}