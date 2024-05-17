<?php

namespace n2n\impl\persistence\orm\property\valobj;

use PHPUnit\Framework\TestCase;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\spec\valobj\scalar\ScalarValueObject;

class ScalarValueObjectTest extends TestCase {

	private EntityModelManager $eem;

	public function setUp(): void {
		$this->emm = new EntityModelManager([ScalarValueObjectTest::class],
				new EntityModelFactory([ScalarValueObjectTest::class]));
	}

	function testPropertyAttributesSet() {
		$entityModel = $this->emm->getEntityModelByClass(ScalarValueObjectTest::class);

		$entityProperty = $entityModel->getLevelEntityPropertyByName('positiveInt');
		$this->assertInstanceOf(ScalarValueObject::class, $entityProperty);
	}
}