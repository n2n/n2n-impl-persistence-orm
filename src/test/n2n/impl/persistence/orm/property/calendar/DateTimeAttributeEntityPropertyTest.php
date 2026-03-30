<?php

namespace n2n\impl\persistence\orm\property\calendar;

use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\test\DbTestPdoUtil;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\impl\persistence\orm\property\calendar\mock\DateTimeAttributeEntityMock;
use n2n\impl\persistence\orm\property\DateTimeEntityProperty;
use n2n\persistence\orm\CorruptedDataException;
use DateTime;
use DateTimeImmutable;

/**
 * Tests for DateTimeEntityProperty using the #[DateTime] attribute with explicit type hints.
 * Verifies that both DateTime (mutable) and DateTimeImmutable work correctly through the
 * attribute-based entity property provider path.
 */
class DateTimeAttributeEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;

	public function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([DateTimeAttributeEntityMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('date_time_attribute_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('mutable_field', 255);
		$columnFactory->createStringColumn('immutable_field', 255);
		$columnFactory->createStringColumn('interface_field', 255);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}

	/**
	 * Verify that properties with #[DateTime] attribute are correctly detected as DateTimeEntityProperty
	 * and that mutability is correctly derived from the type hint.
	 */
	function testEntityPropertyProviderWithAttribute(): void {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(DateTimeAttributeEntityMock::class);

		$mutableProperty = $entityModel->getLevelEntityPropertyByName('mutableField');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $mutableProperty);
		assert($mutableProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('mutable_field', $mutableProperty->getColumnName());

		$immutableProperty = $entityModel->getLevelEntityPropertyByName('immutableField');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $immutableProperty);
		assert($immutableProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('immutable_field', $immutableProperty->getColumnName());

		$interfaceProperty = $entityModel->getLevelEntityPropertyByName('interfaceField');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $interfaceProperty);
		assert($interfaceProperty instanceof DateTimeEntityProperty);
		$this->assertEquals('interface_field', $interfaceProperty->getColumnName());
	}

	/**
	 * Test selecting mutable DateTime values from database.
	 */
	function testSelectionMutable(): void {
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 1,
			'mutable_field' => '1985-09-07 12:01:02',
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entity = $em->find(DateTimeAttributeEntityMock::class, 1);

		$this->assertEquals(1, $entity->id);
		$this->assertInstanceOf(DateTime::class, $entity->mutableField);
		$this->assertEquals('1985-09-07 12:01:02', $entity->mutableField->format('Y-m-d H:i:s'));
	}

	/**
	 * Test selecting immutable DateTimeImmutable values from database.
	 */
	function testSelectionImmutable(): void {
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 1,
			'mutable_field' => '1985-09-07 12:01:02',
			'immutable_field' => '1985-09-07 13:01:02',
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entity = $em->find(DateTimeAttributeEntityMock::class, 1);

		$this->assertInstanceOf(DateTimeImmutable::class, $entity->immutableField);
		$this->assertEquals('1985-09-07 13:01:02', $entity->immutableField->format('Y-m-d H:i:s'));
	}

	/**
	 * Test selecting DateTimeInterface values from database (should resolve to DateTimeImmutable).
	 */
	function testSelectionInterface(): void {
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 1,
			'mutable_field' => '1985-09-07 12:01:02',
			'interface_field' => '1985-09-07 14:01:02',
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entity = $em->find(DateTimeAttributeEntityMock::class, 1);

		$this->assertInstanceOf(DateTimeImmutable::class, $entity->interfaceField);
		$this->assertEquals('1985-09-07 14:01:02', $entity->interfaceField->format('Y-m-d H:i:s'));
	}

	/**
	 * Test selecting null values for nullable DateTime fields.
	 */
	function testSelectionNullValues(): void {
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 1,
			'mutable_field' => '1985-09-07 12:01:02',
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$entity = $em->find(DateTimeAttributeEntityMock::class, 1);

		$this->assertNull($entity->immutableField);
		$this->assertNull($entity->interfaceField);
	}

	/**
	 * Test persisting mutable DateTime.
	 */
	function testPersistMutable(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entity);
		$tx->commit();

		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['mutable_field']);
	}

	/**
	 * Test persisting DateTimeImmutable.
	 */
	function testPersistImmutable(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');
		$entity->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entity);
		$tx->commit();

		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 14:01:02', $rows[0]['immutable_field']);
	}

	/**
	 * Test persisting DateTimeInterface (as DateTimeImmutable instance).
	 */
	function testPersistInterface(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');
		$entity->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entity);
		$tx->commit();

		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 15:01:02', $rows[0]['interface_field']);
	}

	/**
	 * Test full round-trip: persist all types, then read back and verify types are preserved.
	 */
	function testDatabaseRoundTrip(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');
		$entity->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');
		$entity->interfaceField = new DateTimeImmutable('1985-09-07 15:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entity);
		$tx->commit();

		// Same entity manager should return the cached instance
		$cached = $em->find(DateTimeAttributeEntityMock::class, 1);
		$this->assertSame($entity->mutableField, $cached->mutableField);
		$this->assertSame($entity->immutableField, $cached->immutableField);
		$this->assertSame($entity->interfaceField, $cached->interfaceField);

		// Clear cache and read fresh from database
		$em->clear();

		$retrieved = $em->find(DateTimeAttributeEntityMock::class, 1);

		// Verify types
		$this->assertInstanceOf(DateTime::class, $retrieved->mutableField);
		$this->assertInstanceOf(DateTimeImmutable::class, $retrieved->immutableField);
		$this->assertInstanceOf(DateTimeImmutable::class, $retrieved->interfaceField);

		// Verify values
		$this->assertEquals('1985-09-07 13:01:02', $retrieved->mutableField->format('Y-m-d H:i:s'));
		$this->assertEquals('1985-09-07 14:01:02', $retrieved->immutableField->format('Y-m-d H:i:s'));
		$this->assertEquals('1985-09-07 15:01:02', $retrieved->interfaceField->format('Y-m-d H:i:s'));

		// Must be different instances after cache clear
		$this->assertNotSame($entity->mutableField, $retrieved->mutableField);
		$this->assertNotSame($entity->immutableField, $retrieved->immutableField);
		$this->assertNotSame($entity->interfaceField, $retrieved->interfaceField);
	}

	/**
	 * Test merge with mutable DateTime.
	 */
	function testMergeMutable(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$merged = $em->merge($entity);
		$tx->commit();

		$this->assertFalse($entity === $merged);
		$this->assertNotSame($entity->mutableField, $merged->mutableField);
		$this->assertEquals($entity->mutableField, $merged->mutableField);

		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['mutable_field']);
	}

	/**
	 * Test merge with DateTimeImmutable.
	 */
	function testMergeImmutable(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');
		$entity->immutableField = new DateTimeImmutable('1985-09-07 14:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$merged = $em->merge($entity);
		$tx->commit();

		$this->assertEquals($entity->immutableField, $merged->immutableField);

		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 14:01:02', $rows[0]['immutable_field']);
	}

	/**
	 * Test merge update with mutable DateTime.
	 */
	function testMergeUpdateMutable(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$merged = $em->merge($entity);
		$tx->commit();

		// Update and merge again
		$merged->mutableField = new DateTime('2000-01-01 00:00:00');
		$tx = $tm->createTransaction();
		$merged2 = $em->merge($merged);
		$tx->commit();

		$this->assertTrue($merged === $merged2);
		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('2000-01-01 00:00:00', $rows[0]['mutable_field']);
	}

	/**
	 * Test that corrupted date strings throw CorruptedDataException.
	 */
	function testCorruptedSelection(): void {
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 1,
			'mutable_field' => 'not-a-date',
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$this->expectException(CorruptedDataException::class);
		$em->find(DateTimeAttributeEntityMock::class, 1);
	}

	/**
	 * Test repToValue returns correct type based on mutability.
	 */
	function testRepToValueMutability(): void {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(DateTimeAttributeEntityMock::class);

		$mutableProperty = $entityModel->getLevelEntityPropertyByName('mutableField');
		assert($mutableProperty instanceof DateTimeEntityProperty);
		$mutableResult = $mutableProperty->repToValue('1234567890');
		$this->assertInstanceOf(DateTime::class, $mutableResult);
		$this->assertEquals(1234567890, $mutableResult->getTimestamp());

		$immutableProperty = $entityModel->getLevelEntityPropertyByName('immutableField');
		assert($immutableProperty instanceof DateTimeEntityProperty);
		$immutableResult = $immutableProperty->repToValue('1234567890');
		$this->assertInstanceOf(DateTimeImmutable::class, $immutableResult);
		$this->assertEquals(1234567890, $immutableResult->getTimestamp());

		$interfaceProperty = $entityModel->getLevelEntityPropertyByName('interfaceField');
		assert($interfaceProperty instanceof DateTimeEntityProperty);
		$interfaceResult = $interfaceProperty->repToValue('1234567890');
		$this->assertInstanceOf(DateTimeImmutable::class, $interfaceResult);
		$this->assertEquals(1234567890, $interfaceResult->getTimestamp());
	}

	/**
	 * Test valueToRep works with both DateTime and DateTimeImmutable.
	 */
	function testValueToRepBothTypes(): void {
		$entityModel = $this->emPool->getEntityModelManager()->getEntityModelByClass(DateTimeAttributeEntityMock::class);

		$property = $entityModel->getLevelEntityPropertyByName('mutableField');
		assert($property instanceof DateTimeEntityProperty);

		$dateTime = new DateTime('1985-09-07 13:01:02');
		$rep = $property->valueToRep($dateTime);
		$this->assertEquals($dateTime->getTimestamp(), $rep);

		$dateTimeImmutable = new DateTimeImmutable('1985-09-07 14:01:02');
		$rep = $property->valueToRep($dateTimeImmutable);
		$this->assertEquals($dateTimeImmutable->getTimestamp(), $rep);
	}

	/**
	 * Test criteria query with mutable DateTime comparison.
	 */
	function testColumnComparableMutable(): void {
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 1,
			'mutable_field' => '1985-09-07 12:01:02',
		]);
		$this->pdoUtil->insert('date_time_attribute_entity_mock', [
			'id' => 2,
			'mutable_field' => '1985-09-07 13:01:02',
		]);

		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entities = $em->createSimpleCriteria(DateTimeAttributeEntityMock::class,
						['mutableField' => new DateTime('1985-09-07 13:01:02')],
						['mutableField' => 'ASC'])
				->toQuery()->fetchArray();
		$this->assertCount(1, $entities);
		$this->assertEquals(2, $entities[0]->id);
		$this->assertInstanceOf(DateTime::class, $entities[0]->mutableField);
	}

	/**
	 * Test persisting null for nullable fields.
	 */
	function testPersistNullValues(): void {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$entity = new DateTimeAttributeEntityMock();
		$entity->id = 1;
		$entity->mutableField = new DateTime('1985-09-07 13:01:02');
		$entity->immutableField = null;
		$entity->interfaceField = null;

		$tm = $this->emPool->getPdoPool()->getTransactionManager();
		$tx = $tm->createTransaction();
		$em->persist($entity);
		$tx->commit();

		$rows = $this->pdoUtil->select('date_time_attribute_entity_mock');
		$this->assertCount(1, $rows);
		$this->assertEquals('1985-09-07 13:01:02', $rows[0]['mutable_field']);
		$this->assertNull($rows[0]['immutable_field']);
		$this->assertNull($rows[0]['interface_field']);
	}
}
