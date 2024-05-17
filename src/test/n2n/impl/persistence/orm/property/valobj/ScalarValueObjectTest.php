<?php

namespace n2n\impl\persistence\orm\property\valobj;

use PHPUnit\Framework\TestCase;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\spec\valobj\scalar\ScalarValueObject;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\impl\persistence\orm\property\valobj\mock\ScalarValueObjectEntityMock;
use n2n\impl\persistence\orm\property\ScalarValueObjectEntityProperty;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\impl\persistence\orm\live\mock\LazyContainerMock;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use n2n\test\DbTestPdoUtil;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\persistence\orm\CorruptedDataException;
use n2n\impl\persistence\orm\property\valobj\mock\PositiveInt;
use n2n\util\ex\ExUtils;
use n2n\spec\valobj\err\IllegalValueException;

class ScalarValueObjectTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;

	public function setUp(): void {
		$this->emm = new EntityModelManager([ScalarValueObjectEntityMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));

		$this->emPool = GeneralTestEnv::setUpEmPool([ScalarValueObjectEntityMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('scalar_value_object_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('positive_int', 32);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}

	function testEntityPropertyProvider() {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(ScalarValueObjectEntityMock::class);

		$entityProperty = $entityModel->getLevelEntityPropertyByName('positiveInt');
		$this->assertInstanceOf(ScalarValueObjectEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof ScalarValueObjectEntityProperty);
		$namedTypes = $entityProperty->getScalarTypeConstraint()->getNamedTypeConstraints();
		$this->assertCount(1, $namedTypes);
		$this->assertEquals('int', $namedTypes[0]->getTypeName());
		$this->assertTrue($namedTypes[0]->allowsNull());
	}

	function testSelection() {
		$this->pdoUtil->insert('scalar_value_object_entity_mock', ['id' => 1, 'positive_int' => 3]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityMock = $em->find(ScalarValueObjectEntityMock::class, 1);

		$this->assertEquals(1, $entityMock->id);
		$this->assertEquals(3, $entityMock->positiveInt->toScalar());
	}

	function testCorruptedSelection() {
		$this->pdoUtil->insert('scalar_value_object_entity_mock', ['id' => 1, 'positive_int' => -1]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$this->expectException(CorruptedDataException::class);
		$entityMock = $em->find(ScalarValueObjectEntityMock::class, 1);
	}

	/**
	 * @throws IllegalValueException
	 */
	function testColumnComparable() {
		$this->pdoUtil->insert('scalar_value_object_entity_mock', ['id' => 1, 'positive_int' => 2]);
		$this->pdoUtil->insert('scalar_value_object_entity_mock', ['id' => 2, 'positive_int' => 1]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMocks = $em->createSimpleCriteria(ScalarValueObjectEntityMock::class, ['positiveInt' => 2], ['positiveInt' => 'ASC'])
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(ScalarValueObjectEntityMock::class, $entityMocks[0]);
		$this->assertEquals(2, $entityMocks[0]->positiveInt->toScalar());


		$entityMocks = $em->createSimpleCriteria(ScalarValueObjectEntityMock::class,
						['positiveInt' => new PositiveInt(1)], ['positiveInt' => 'ASC'])
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(ScalarValueObjectEntityMock::class, $entityMocks[0]);
		$this->assertEquals(1, $entityMocks[0]->positiveInt->toScalar());
	}
}