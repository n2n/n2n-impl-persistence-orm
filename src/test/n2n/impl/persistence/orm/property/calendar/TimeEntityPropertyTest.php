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
use n2n\util\ex\ExUtils;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\impl\persistence\orm\property\valobj\mock\ShortString;
use n2n\impl\persistence\orm\property\calendar\mock\TimeEntityMock;
use n2n\spec\dbo\err\DboException;
use n2n\util\calendar\Time;

class TimeEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;

	public function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([TimeEntityMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('time_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('first_time', 8);
		$columnFactory->createStringColumn('second_time', 8);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}

	function testEntityPropertyProvider() {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(TimeEntityMock::class);

		$entityProperty = $entityModel->getLevelEntityPropertyByName('firstTime');
		$this->assertInstanceOf(TimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof TimeEntityProperty);
		$this->assertEquals('first_time', $entityProperty->getColumnName());


		$entityProperty = $entityModel->getLevelEntityPropertyByName('secondTime');
		$this->assertInstanceOf(TimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof TimeEntityProperty);
		$this->assertEquals('second_time', $entityProperty->getColumnName());

	}

	/**
	 * @throws DboException
	 */
	function testSelection() {
		$this->pdoUtil->insert('time_entity_mock', ['id' => 1, 'first_time' => '12:01:02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityMock = $em->find(TimeEntityMock::class, 1);

		$this->assertEquals(1, $entityMock->id);
		$this->assertEquals('12:01:02', $entityMock->firstTime);
		$this->assertNull($entityMock->secondTime);
	}

	/**
	 * @throws DboException
	 */
	function testCorruptedSelection() {
		$this->pdoUtil->insert('time_entity_mock', ['id' => 1, 'first_time' => '50:00:12']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$this->expectException(CorruptedDataException::class);
		$entityMock = $em->find(TimeEntityMock::class, 1);
	}

	/**
	 * @throws IllegalValueException
	 * @throws DboException
	 */
	function testColumnComparable() {
		$this->pdoUtil->insert('time_entity_mock', ['id' => 1, 'first_time' => '12:01:02']);
		$this->pdoUtil->insert('time_entity_mock', ['id' => 2, 'first_time' => '13:01:02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMocks = $em->createSimpleCriteria(TimeEntityMock::class, ['firstTime' => new Time('13:01:02')],
						['firstTime' => 'ASC'])
				->toQuery()->fetchArray();
		$this->assertCount(1, $entityMocks);
		$this->assertInstanceOf(TimeEntityMock::class, $entityMocks[0]);
		$this->assertEquals('13:01:02', (string) $entityMocks[0]->firstTime);
	}

	/**
	 * @throws DboException
	 */
	function testPersist(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new TimeEntityMock();
		$entityMock->id = 1;
		$entityMock->firstTime = new Time('13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();


		$rows = $this->pdoUtil->select('time_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('13:01:02', $rows[0]['first_time']);
	}

	/**
	 * @throws DboException
	 */
	function testMerge(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new TimeEntityMock();
		$entityMock->id = 1;
		$entityMock->firstTime = new Time('13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock2 = $em->merge($entityMock);
		$tx->commit();

		$this->assertFalse($entityMock === $entityMock2);
		$this->assertTrue($entityMock->firstTime === $entityMock2->firstTime);
		$rows = $this->pdoUtil->select('time_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('13:01:02', $rows[0]['first_time']);

		$entityMock2->firstTime = new Time('14:01:02');
		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock3 = $em->merge($entityMock2);
		$tx->commit();

		$this->assertTrue($entityMock2 === $entityMock3);
		$this->assertTrue($entityMock2->firstTime === $entityMock3->firstTime);
		$rows = $this->pdoUtil->select('time_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('14:01:02', $rows[0]['first_time']);
	}

	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLifecycle() {
		$tm = $this->pdoPool->getTransactionManager();
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();

		$svoem = new TimeEntityMock();
		$svoem->id = 1;
		$svoem->firstTime = new Time('14:01:02');

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($svoem);

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEquals(0, $this->lifecycleListener->getNum());
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[TimeEntityMock::class]);
		$this->assertCount(2, $this->lifecycleListener->events[TimeEntityMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(TimeEntityMock::class, $svoem->id);
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[TimeEntityMock::class]);


		// UPDATE TABLE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(TimeEntityMock::class, $svoem->id);
		$svoem->secondTime = new Time('04:01:02');

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(4, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[TimeEntityMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[TimeEntityMock::class]);
		$events = $this->lifecycleListener->events[TimeEntityMock::class];
		$this->assertCount(4, $events);

		$this->assertTrue($events[2]->containsChangesFor('secondTime'));
		$this->assertFalse($events[2]->containsChangesFor('firstTime'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('secondTime'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('firstTime'));


		// REMOVE

		$tx = $tm->createTransaction();

		$svoem = $this->tem()->find(TimeEntityMock::class, $svoem->id);

		$this->tem()->remove($svoem);

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(5, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[TimeEntityMock::class]);

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[TimeEntityMock::class]);
	}
}