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
use n2n\impl\persistence\orm\property\calendar\mock\DateTimeEntityMock;
use n2n\spec\dbo\err\DboException;
use n2n\impl\persistence\orm\property\DateTimeEntityProperty;
use DateTime;

class DateTimeEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;

	public function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([DateTimeEntityMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('date_time_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('first_date_time', 8);
		$columnFactory->createStringColumn('second_date_time', 8);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}

	function testEntityPropertyProvider() {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(DateTimeEntityMock::class);

		$entityProperty = $entityModel->getLevelEntityPropertyByName('firstDateTime');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('first_date_time', $entityProperty->getColumnName());


		$entityProperty = $entityModel->getLevelEntityPropertyByName('secondDateTime');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('second_date_time', $entityProperty->getColumnName());

	}

	/**
	 * @throws DboException
	 */
	function testSelection() {
		$this->pdoUtil->insert('date_time_entity_mock', ['id' => 1, 'first_date_time' => '1985-09-07 12:01:02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityMock = $em->find(DateTimeEntityMock::class, 1);

		$this->assertEquals(1, $entityMock->id);
		$this->assertEquals(new \DateTime('1985-09-07 12:01:02'), $entityMock->firstDateTime);
		$this->assertNull($entityMock->secondDateTime);
	}

	/**
	 * @throws DboException
	 */
	function testCorruptedSelection() {
		$this->pdoUtil->insert('date_time_entity_mock', ['id' => 1, 'first_date_time' => 'heute']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$this->expectException(CorruptedDataException::class);
		$entityMock = $em->find(DateTimeEntityMock::class, 1);
	}

	/**
	 * @throws IllegalValueException
	 * @throws DboException
	 */
	function testColumnComparable() {
		$this->pdoUtil->insert('date_time_entity_mock', ['id' => 1, 'first_date_time' => '1985-09-0712:01:02']);
		$this->pdoUtil->insert('date_time_entity_mock', ['id' => 2, 'first_date_time' => '1985-09-07 13:01:02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMocks = $em->createSimpleCriteria(DateTimeEntityMock::class, ['firstDateTime' => new DateTime('1985-09-07 13:01:02')],
						['firstDateTime' => 'ASC'])
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(DateTimeEntityMock::class, $entityMocks[0]);
		$this->assertEquals(new DateTime('1985-09-07 13:01:02'), $entityMocks[0]->firstDateTime);
	}

	/**
	 * @throws DboException
	 */
	function testColumnComparableLike() {
		$this->pdoUtil->insert('date_time_entity_mock', ['id' => 1, 'first_date_time' => '1985-09-07 12:01:02']);
		$this->pdoUtil->insert('date_time_entity_mock', ['id' => 2, 'first_date_time' => '1985-09-07 13:01:02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMocks = $em->createCriteria()->select('dtem')->from(DateTimeEntityMock::class, 'dtem')
				->where()->match('dtem.firstDateTime', 'LIKE', new DateTime('1985-09-07 13:01:02'))->endClause()
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(DateTimeEntityMock::class, $entityMocks[0]);
		$this->assertEquals(new DateTime('1985-09-07 13:01:02'), $entityMocks[0]->firstDateTime);

		$entityMocks = $em->createCriteria()->select('dtem')->from(DateTimeEntityMock::class, 'dtem')
				->where()->match('dtem.firstDateTime', 'LIKE', '%13%')->endClause()
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(DateTimeEntityMock::class, $entityMocks[0]);
		$this->assertEquals(new DateTime('1985-09-07 13:01:02'), $entityMocks[0]->firstDateTime);

		$entityMocks = $em->createCriteria()->select('dtem')->from(DateTimeEntityMock::class, 'dtem')
				->where()->match('dtem.firstDateTime', 'NOT LIKE', '%13%')->endClause()
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(DateTimeEntityMock::class, $entityMocks[0]);
		$this->assertEquals(new DateTime('1985-09-07 12:01:02'), $entityMocks[0]->firstDateTime);
	}

	/**
	 * @throws DboException
	 */
	function testPersist(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeEntityMock();
		$entityMock->id = 1;
		$entityMock->firstDateTime = new DateTime('1985-09-07 13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();


		$rows = $this->pdoUtil->select('date_time_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['first_date_time']);
	}

	/**
	 * @throws DboException
	 */
	function testMerge(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeEntityMock();
		$entityMock->id = 1;
		$entityMock->firstDateTime = new DateTime('1985-09-07 13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock2 = $em->merge($entityMock);
		$tx->commit();

		$this->assertFalse($entityMock === $entityMock2);
		$this->assertNotSame($entityMock->firstDateTime, $entityMock2->firstDateTime);
		$this->assertEquals($entityMock->firstDateTime, $entityMock2->firstDateTime);
		$rows = $this->pdoUtil->select('date_time_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['first_date_time']);

		$entityMock2->firstDateTime = new DateTime('1985-09-07 14:01:02');
		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock3 = $em->merge($entityMock2);
		$tx->commit();

		$this->assertTrue($entityMock2 === $entityMock3);
		$this->assertTrue($entityMock2->firstDateTime === $entityMock3->firstDateTime);
		$rows = $this->pdoUtil->select('date_time_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 14:01:02', $rows[0]['first_date_time']);
	}

	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLifecycle() {
		$tm = $this->pdoPool->getTransactionManager();
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();

		$svoem = new DateTimeEntityMock();
		$svoem->id = 1;
		$svoem->firstDateTime = new DateTime('1985-09-07 14:01:02');

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($svoem);

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEquals(0, $this->lifecycleListener->getNum());
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[DateTimeEntityMock::class]);
		$this->assertCount(2, $this->lifecycleListener->events[DateTimeEntityMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(DateTimeEntityMock::class, $svoem->id);
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[DateTimeEntityMock::class]);


		// UPDATE TABLE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(DateTimeEntityMock::class, $svoem->id);
		$svoem->secondDateTime = new DateTime('1985-09-07 04:01:02');

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(4, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[DateTimeEntityMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[DateTimeEntityMock::class]);
		$events = $this->lifecycleListener->events[DateTimeEntityMock::class];
		$this->assertCount(4, $events);

		$this->assertTrue($events[2]->containsChangesFor('secondDateTime'));
		$this->assertFalse($events[2]->containsChangesFor('firstDateTime'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('secondDateTime'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('firstDateTime'));


		// REMOVE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(DateTimeEntityMock::class, $svoem->id);

		$this->tem()->remove($svoem);

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(5, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[DateTimeEntityMock::class]);

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[DateTimeEntityMock::class]);
	}
}