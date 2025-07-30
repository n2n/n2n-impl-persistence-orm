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
namespace n2n\impl\persistence\orm\property\calendar;

use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\test\DbTestPdoUtil;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\persistence\orm\CorruptedDataException;
use n2n\spec\valobj\err\IllegalValueException;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\impl\persistence\orm\property\calendar\mock\DateEntityMock;
use n2n\spec\dbo\err\DboException;
use n2n\util\calendar\Date;

class DateEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;

	public function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([DateEntityMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('date_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('first_date', 8);
		$columnFactory->createStringColumn('second_date', 8);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}

	function testEntityPropertyProvider() {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(DateEntityMock::class);

		$entityProperty = $entityModel->getLevelEntityPropertyByName('firstDate');
		$this->assertInstanceOf(DateEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateEntityProperty);
		$this->assertEquals('first_date', $entityProperty->getColumnName());


		$entityProperty = $entityModel->getLevelEntityPropertyByName('secondDate');
		$this->assertInstanceOf(DateEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateEntityProperty);
		$this->assertEquals('second_date', $entityProperty->getColumnName());

	}

	/**
	 * @throws DboException
	 */
	function testSelection() {
		$this->pdoUtil->insert('date_entity_mock', ['id' => 1, 'first_date' => '2023-10-01']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityMock = $em->find(DateEntityMock::class, 1);

		$this->assertEquals(1, $entityMock->id);
		$this->assertEquals('2023-10-01', $entityMock->firstDate);
		$this->assertNull($entityMock->secondDate);
	}

	/**
	 * @throws DboException
	 */
	function testCorruptedSelection() {
		$this->pdoUtil->insert('date_entity_mock', ['id' => 1, 'first_date' => '0020:40:01']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$this->expectException(CorruptedDataException::class);
		$entityMock = $em->find(DateEntityMock::class, 1);
	}

	/**
	 * @throws IllegalValueException
	 * @throws DboException
	 */
	function testColumnComparable() {
		$this->pdoUtil->insert('date_entity_mock', ['id' => 1, 'first_date' => '2023-10-01']);
		$this->pdoUtil->insert('date_entity_mock', ['id' => 2, 'first_date' => '2023-10-02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMocks = $em->createSimpleCriteria(DateEntityMock::class, ['firstDate' => new Date('2023-10-02')],
						['firstDate' => 'ASC'])
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(DateEntityMock::class, $entityMocks[0]);
		$this->assertEquals('2023-10-02', (string) $entityMocks[0]->firstDate);
	}

	/**
	 * @throws DboException
	 */
	function testPersist(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateEntityMock();
		$entityMock->id = 1;
		$entityMock->firstDate = new Date('2023-10-02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();


		$rows = $this->pdoUtil->select('date_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('2023-10-02', $rows[0]['first_date']);
	}

	/**
	 * @throws DboException
	 */
	function testMerge(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateEntityMock();
		$entityMock->id = 1;
		$entityMock->firstDate = new Date('2023-10-02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock2 = $em->merge($entityMock);
		$tx->commit();

		$this->assertFalse($entityMock === $entityMock2);
		$this->assertTrue($entityMock->firstDate === $entityMock2->firstDate);
		$rows = $this->pdoUtil->select('date_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('2023-10-02', $rows[0]['first_date']);

		$entityMock2->firstDate = new Date('2023-10-03');
		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock3 = $em->merge($entityMock2);
		$tx->commit();

		$this->assertTrue($entityMock2 === $entityMock3);
		$this->assertTrue($entityMock2->firstDate === $entityMock3->firstDate);
		$rows = $this->pdoUtil->select('date_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('2023-10-03', $rows[0]['first_date']);
	}

	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLifecycle() {
		$tm = $this->pdoPool->getTransactionManager();
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();

		$svoem = new DateEntityMock();
		$svoem->id = 1;
		$svoem->firstDate = new Date('2023-10-03');

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($svoem);

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEquals(0, $this->lifecycleListener->getNum());
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[DateEntityMock::class]);
		$this->assertCount(2, $this->lifecycleListener->events[DateEntityMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(DateEntityMock::class, $svoem->id);
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[DateEntityMock::class]);


		// UPDATE TABLE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(DateEntityMock::class, $svoem->id);
		$svoem->secondDate = new Date('2023-10-03');

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(4, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[DateEntityMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[DateEntityMock::class]);
		$events = $this->lifecycleListener->events[DateEntityMock::class];
		$this->assertCount(4, $events);

		$this->assertTrue($events[2]->containsChangesFor('secondDate'));
		$this->assertFalse($events[2]->containsChangesFor('firstDate'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('secondDate'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('firstDate'));


		// REMOVE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(DateEntityMock::class, $svoem->id);

		$this->tem()->remove($svoem);

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(5, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[DateEntityMock::class]);

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[DateEntityMock::class]);
	}
}