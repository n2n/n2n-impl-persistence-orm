<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\persistence\orm\property\valobj;

use PHPUnit\Framework\TestCase;
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
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\impl\persistence\orm\property\valobj\mock\ShortString;

class ScalarValueObjectEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;

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
		$columnFactory->createStringColumn('short_string', 5);

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

		$this->assertTrue($entityMock2 === $entityMock3);
		$this->assertTrue($entityMock2->positiveInt === $entityMock3->positiveInt);
		$rows = $this->pdoUtil->select('scalar_value_object_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals(4, $rows[0]['positive_int']);
	}

	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLifecycle() {
		$tm = $this->pdoPool->getTransactionManager();
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();

		$svoem = new ScalarValueObjectEntityMock();
		$svoem->id = 1;
		$svoem->positiveInt = ExUtils::try(fn () => new PositiveInt(4));

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($svoem);

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEquals(0, $this->lifecycleListener->getNum());
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[ScalarValueObjectEntityMock::class]);
		$this->assertCount(2, $this->lifecycleListener->events[ScalarValueObjectEntityMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(ScalarValueObjectEntityMock::class, $svoem->id);
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[ScalarValueObjectEntityMock::class]);


		// UPDATE TABLE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(ScalarValueObjectEntityMock::class, $svoem->id);
		$svoem->shortString = ExUtils::try(fn () => new ShortString('hoi'));

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(4, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[ScalarValueObjectEntityMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[ScalarValueObjectEntityMock::class]);
		$events = $this->lifecycleListener->events[ScalarValueObjectEntityMock::class];
		$this->assertCount(4, $events);

		$this->assertTrue($events[2]->containsChangesFor('shortString'));
		$this->assertFalse($events[2]->containsChangesFor('positiveInt'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('shortString'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('positiveInt'));


		// REMOVE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(ScalarValueObjectEntityMock::class, $svoem->id);

		$this->tem()->remove($svoem);

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(5, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[ScalarValueObjectEntityMock::class]);

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[ScalarValueObjectEntityMock::class]);
	}
}