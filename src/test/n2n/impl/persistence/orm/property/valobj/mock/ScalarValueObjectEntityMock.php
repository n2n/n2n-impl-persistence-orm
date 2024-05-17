<?php

namespace n2n\impl\persistence\orm\property\valobj\mock;

use n2n\persistence\orm\model\EntityModelManager;
use n2n\impl\persistence\orm\property\mock\EntityPropertiesMock;
use n2n\impl\persistence\orm\property\mock\TargetMock;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;

class ScalarValueObjectEntityMock {
	public int $id;
	public PositiveInt $positiveInt;


}