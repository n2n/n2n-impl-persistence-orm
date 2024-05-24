<?php

namespace n2n\impl\persistence\orm\property\valobj;

use PHPUnit\Framework\TestCase;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\impl\persistence\orm\property\valobj\mock\ScalarValueObjectEntityMock;
use n2n\impl\persistence\orm\property\ScalarValueObjectEntityProperty;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\test\DbTestPdoUtil;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\persistence\orm\CorruptedDataException;
use n2n\impl\persistence\orm\property\valobj\mock\PositiveInt;
use n2n\spec\valobj\err\IllegalValueException;
use n2n\util\ex\ExUtils;

class ScalarValueObjectTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;

	public function setUp(): void {
//		$this->emm = new EntityModelManager([ScalarValueObjectEntityMock::class],
//				new EntityModelFactory([CommonEntityPropertyProvider::class]));

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

	function testPersist(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new ScalarValueObjectEntityMock();
		$entityMock->id = 1;
		$entityMock->positiveInt = ExUtils::try(fn () => new PositiveInt(3));

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();


		$rows = $this->pdoUtil->select('scalar_value_object_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals(3, $rows[0]['positive_int']);
	}

	function testMerge(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new ScalarValueObjectEntityMock();
		$entityMock->id = 1;
		$entityMock->positiveInt = ExUtils::try(fn () => new PositiveInt(3));

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock2 = $em->merge($entityMock);
		$tx->commit();

		$this->assertFalse($entityMock === $entityMock2);
		$this->assertTrue($entityMock->positiveInt === $entityMock2->positiveInt);
		$rows = $this->pdoUtil->select('scalar_value_object_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals(3, $rows[0]['positive_int']);

		$entityMock2->positiveInt = ExUtils::try(fn () => new PositiveInt(4));
		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock3 = $em->merge($entityMock2);
		$tx->commit();

		$this->assertFalse($entityMock === $entityMock2);
		$this->assertTrue($entityMock->positiveInt === $entityMock2->positiveInt);
		$rows = $this->pdoUtil->select('scalar_value_object_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals(4, $rows[0]['positive_int']);
	}
}