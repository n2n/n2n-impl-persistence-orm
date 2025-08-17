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
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\property\calendar\mock\DateTimeInterfaceEntityMock;
use n2n\spec\dbo\err\DboException;
use n2n\impl\persistence\orm\property\DateTimeEntityProperty;
use DateTime;
use DateTimeImmutable;
use n2n\persistence\orm\CorruptedDataException;

class DateTimeEntityPropertyImmutableTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;

	public function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([DateTimeInterfaceEntityMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('date_time_interface_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('mutable_field', 255);
		$columnFactory->createStringColumn('immutable_field', 255);
		$columnFactory->createStringColumn('interface_field', 255);
		$columnFactory->createStringColumn('nullable_mutable', 255);
		$columnFactory->createStringColumn('nullable_immutable', 255);
		$columnFactory->createStringColumn('nullable_interface', 255);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}

	function testEntityPropertyProvider() {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(DateTimeInterfaceEntityMock::class);

		$entityProperty = $entityModel->getLevelEntityPropertyByName('mutableField');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('mutable_field', $entityProperty->getColumnName());

		$entityProperty = $entityModel->getLevelEntityPropertyByName('immutableField');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('immutable_field', $entityProperty->getColumnName());

		$entityProperty = $entityModel->getLevelEntityPropertyByName('interfaceField');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $entityProperty);
		assert($entityProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('interface_field', $entityProperty->getColumnName());
	}

	/**
	 * @throws DboException
	 */
	function testSelection() {
		$this->pdoUtil->insert('date_time_interface_entity_mock', ['id' => 1, 'mutable_field' => '1985-09-07 12:01:02',
				'immutable_field' => '1985-09-07 13:01:02', 'interface_field' => '1985-09-07 14:01:02']);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityMock = $em->find(DateTimeInterfaceEntityMock::class, 1);

		$this->assertEquals(1, $entityMock->id);
		$this->assertEquals(new DateTime('1985-09-07 12:01:02'), $entityMock->mutableField);
		$this->assertEquals(new DateTimeImmutable('1985-09-07 13:01:02'), $entityMock->immutableField);
		$this->assertEquals(new DateTimeImmutable('1985-09-07 14:01:02'), $entityMock->interfaceField);
		$this->assertNull($entityMock->nullableMutable);
		$this->assertNull($entityMock->nullableImmutable);
		$this->assertNull($entityMock->nullableInterface);
	}

	/**
	 * @throws DboException
	 */
	function testPersist(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeInterfaceEntityMock();
		$entityMock->id = 1;
		$entityMock->mutableField = new DateTime('1985-09-07 13:01:02');
		$entityMock->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');
		$entityMock->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();

		$rows = $this->pdoUtil->select('date_time_interface_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['mutable_field']);
		$this->assertEquals('1985-09-07 14:01:02', $rows[0]['immutable_field']);
		$this->assertEquals('1985-09-07 15:01:02', $rows[0]['interface_field']);
	}

	/**
	 * @throws DboException
	 */
	function testMerge(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeInterfaceEntityMock();
		$entityMock->id = 1;
		$entityMock->mutableField = new DateTime('1985-09-07 13:01:02');
		$entityMock->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');
		$entityMock->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$entityMock2 = $em->merge($entityMock);
		$tx->commit();

		$this->assertFalse($entityMock === $entityMock2);
		$this->assertNotSame($entityMock->mutableField, $entityMock2->mutableField);
		$this->assertEquals($entityMock->mutableField, $entityMock2->mutableField);

		$this->assertEquals($entityMock->immutableField, $entityMock2->immutableField);
		$this->assertEquals($entityMock->interfaceField, $entityMock2->interfaceField);

		$rows = $this->pdoUtil->select('date_time_interface_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['mutable_field']);
	}

	/**
	 * Test null handling for all three types
	 */
	function testNullHandling(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeInterfaceEntityMock();
		$entityMock->id = 2;
		$entityMock->mutableField = new DateTime('1985-09-07 13:01:02');
		$entityMock->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');
		$entityMock->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();

		$entityMock->nullableMutable = null;
		$entityMock->nullableImmutable = null;
		$entityMock->nullableInterface = null;

		$tx = $tm->createTransaction();
		$em->merge($entityMock);
		$tx->commit();

		$retrieved = $em->find(DateTimeInterfaceEntityMock::class, 2);
		$this->assertNull($retrieved->nullableMutable);
		$this->assertNull($retrieved->nullableImmutable);
		$this->assertNull($retrieved->nullableInterface);
	}

	/**
	 * empty strings should throw exceptions (expected behavior)
	 * @throws DboException
	 */
	function testEmptyString(): void {
		$this->expectException(CorruptedDataException::class);
		
		$this->pdoUtil->insert('date_time_interface_entity_mock', [
			'id' => 3, 
			'mutable_field' => '', 
			'immutable_field' => '', 
			'interface_field' => ''
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$em->find(DateTimeInterfaceEntityMock::class, 3);
	}

	/**
	 * empty strings should throw exceptions (expected behavior)
	 * @throws DboException
	 */
	function testInvalidFormat(): void {
		$this->expectException(CorruptedDataException::class);

		$this->pdoUtil->insert('date_time_interface_entity_mock', [
				'id' => 3,
				'mutable_field' => 'asdf',
				'immutable_field' => 'asdf',
				'interface_field' => 'asdf'
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$em->find(DateTimeInterfaceEntityMock::class, 3);
	}

	/**
	 * Test database round-trip with all three types
	 * @throws DboException
	 */
	function testDatabaseRoundTrip(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeInterfaceEntityMock();
		$entityMock->id = 5;
		$entityMock->mutableField = new DateTime('1985-09-07 13:01:02');
		$entityMock->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');
		$entityMock->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();
		$sameRetrieved = $em->find(DateTimeInterfaceEntityMock::class, 5);
		// before $em->clear() the reference object should remain the same
		$this->assertSame($entityMock->mutableField, $sameRetrieved->mutableField);
		$this->assertSame($entityMock->immutableField, $sameRetrieved->immutableField);
		$this->assertSame($entityMock->interfaceField, $sameRetrieved->interfaceField);
		$em->clear();

		$retrieved = $em->find(DateTimeInterfaceEntityMock::class, 5);

		$this->assertEquals('1985-09-07 13:01:02', $retrieved->mutableField->format('Y-m-d H:i:s'));
		$this->assertEquals('1985-09-07 14:01:02', $retrieved->immutableField->format('Y-m-d H:i:s'));
		$this->assertEquals('1985-09-07 15:01:02', $retrieved->interfaceField->format('Y-m-d H:i:s'));

		$this->assertInstanceOf(DateTime::class, $retrieved->mutableField);
		$this->assertInstanceOf(DateTimeImmutable::class, $retrieved->immutableField);
		$this->assertInstanceOf(DateTimeImmutable::class, $retrieved->interfaceField);

		$this->assertNotSame($entityMock->mutableField, $retrieved->mutableField);
		$this->assertNotSame($entityMock->immutableField, $retrieved->immutableField);
		$this->assertNotSame($entityMock->interfaceField, $retrieved->interfaceField);
	}

	/**
	 * Test type conversion behavior - conversion happens during database operations, not direct assignment
	 */
	function testTypeConversion(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entityMock = new DateTimeInterfaceEntityMock();
		$entityMock->id = 6;

		$entityMock->mutableField = new DateTime('1985-09-07 13:01:02');
		$entityMock->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');
		$entityMock->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entityMock);
		$tx->commit();

		$retrieved = $em->find(DateTimeInterfaceEntityMock::class, 6);

		$this->assertInstanceOf(DateTime::class, $retrieved->mutableField);
		$this->assertInstanceOf(DateTimeImmutable::class, $retrieved->immutableField);
		$this->assertInstanceOf(DateTimeImmutable::class, $retrieved->interfaceField);

		$this->assertEquals('1985-09-07 13:01:02', $retrieved->mutableField->format('Y-m-d H:i:s'));
		$this->assertEquals('1985-09-07 14:01:02', $retrieved->immutableField->format('Y-m-d H:i:s'));
		$this->assertEquals('1985-09-07 15:01:02', $retrieved->interfaceField->format('Y-m-d H:i:s'));
	}

	/**
	 * Test repToValue method returns correct types for all property types
	 */
	function testRepToValueTypeHandling(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityModel = $em->getEntityModelManager()->getEntityModelByClass(DateTimeInterfaceEntityMock::class);

		$mutableProperty = $entityModel->getLevelEntityPropertyByName('mutableField');
		assert($mutableProperty instanceof DateTimeEntityProperty);
		
		$timestamp = '1234567890';
		$mutableResult = $mutableProperty->repToValue($timestamp);
		$this->assertInstanceOf(DateTime::class, $mutableResult);
		$this->assertEquals(1234567890, $mutableResult->getTimestamp());

		$immutableProperty = $entityModel->getLevelEntityPropertyByName('immutableField');
		assert($immutableProperty instanceof DateTimeEntityProperty);
		
		$immutableResult = $immutableProperty->repToValue($timestamp);
		$this->assertInstanceOf(DateTimeImmutable::class, $immutableResult);
		$this->assertEquals(1234567890, $immutableResult->getTimestamp());
		
		// Test interface field (DateTimeInterface - should be immutable based on setter)
		$interfaceProperty = $entityModel->getLevelEntityPropertyByName('interfaceField');
		assert($interfaceProperty instanceof DateTimeEntityProperty);
		
		$interfaceResult = $interfaceProperty->repToValue($timestamp);
		$this->assertInstanceOf(DateTimeImmutable::class, $interfaceResult);
		$this->assertEquals(1234567890, $interfaceResult->getTimestamp());
	}

	/**
	 * Test valueToRep method works with all DateTime types
	 * @throws \DateMalformedStringException
	 */
	function testValueToRepTypeHandling(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entityModel = $em->getEntityModelManager()->getEntityModelByClass(DateTimeInterfaceEntityMock::class);
		
		$property = $entityModel->getLevelEntityPropertyByName('mutableField');
		assert($property instanceof DateTimeEntityProperty);

		$dateTime = new DateTime('1985-09-07 13:01:02');
		$timestamp = $property->valueToRep($dateTime);
		$this->assertEquals('1985-09-07 13:01:02', DateTime::createFromTimestamp($timestamp)->format('Y-m-d H:i:s'));

		$dateTimeImmutable = new DateTimeImmutable('1985-09-07 14:01:02');
		$timestampImmutable = $property->valueToRep($dateTimeImmutable);
		$this->assertEquals('1985-09-07 14:01:02', DateTime::createFromTimestamp($timestampImmutable)->format('Y-m-d H:i:s'));

		$this->expectException(\InvalidArgumentException::class);
		$property->valueToRep(null);
	}
}
