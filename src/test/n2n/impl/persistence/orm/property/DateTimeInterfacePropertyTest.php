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
namespace n2n\impl\persistence\orm\property;

use PHPUnit\Framework\TestCase;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\mock\DateTimeInterfaceMock;
use n2n\util\DateUtils;
use n2n\persistence\orm\store\CommonValueHash;
use n2n\persistence\orm\store\operation\MergeOperation;
use n2n\persistence\orm\EntityManager;
use n2n\util\magic\MagicContext;

/**
 * Test class to verify DateTimeInterface compatibility with ORM
 */
class DateTimeInterfacePropertyTest extends TestCase {
	private EntityModelManager $emm;
	private DateTimeEntityProperty $dateTimeProperty;
	private MergeOperation $mockMergeOperation;
	private MagicContext $mockMagicContext;

	public function setUp(): void {
		$this->emm = new EntityModelManager([DateTimeInterfaceMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));
		
		$entityModel = $this->emm->getEntityModelByClass(DateTimeInterfaceMock::class);
		$this->dateTimeProperty = $entityModel->getLevelEntityPropertyByName('dateTimeProperty');
		
		// Create mocks for testing
		$this->mockMergeOperation = $this->createMock(MergeOperation::class);
		$this->mockMagicContext = $this->createMock(MagicContext::class);
	}

	/**
	 * Test that the entity model recognizes all DateTime properties correctly
	 */
	public function testEntityModelPropertyRecognition() {
		$entityModel = $this->emm->getEntityModelByClass(DateTimeInterfaceMock::class);

		// Test that all DateTime properties are recognized as DateTimeEntityProperty
		$this->assertTrue($entityModel->containsEntityPropertyName('dateTimeProperty'));
		$dateTimeEp = $entityModel->getLevelEntityPropertyByName('dateTimeProperty');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $dateTimeEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('dateTimeImmutableProperty'));
		$dateTimeImmutableEp = $entityModel->getLevelEntityPropertyByName('dateTimeImmutableProperty');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $dateTimeImmutableEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('dateTimeInterfaceProperty'));
		$dateTimeInterfaceEp = $entityModel->getLevelEntityPropertyByName('dateTimeInterfaceProperty');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $dateTimeInterfaceEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('nullableDateTimeProperty'));
		$nullableDateTimeEp = $entityModel->getLevelEntityPropertyByName('nullableDateTimeProperty');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $nullableDateTimeEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('nullableDateTimeImmutableProperty'));
		$nullableDateTimeImmutableEp = $entityModel->getLevelEntityPropertyByName('nullableDateTimeImmutableProperty');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $nullableDateTimeImmutableEp);
	}

	/**
	 * Test valueToRep method with DateTime
	 */
	public function testValueToRepWithDateTime() {
		$dateTime = new \DateTime('2024-01-15 14:30:00');
		$rep = $this->dateTimeProperty->valueToRep($dateTime);
		
		$this->assertEquals($dateTime->getTimestamp(), $rep);
		$this->assertTrue(is_string($rep));
	}

	/**
	 * Test valueToRep method with DateTimeImmutable
	 */
	public function testValueToRepWithDateTimeImmutable() {
		$dateTimeImmutable = new \DateTimeImmutable('2024-01-15 14:30:00');
		$rep = $this->dateTimeProperty->valueToRep($dateTimeImmutable);
		
		$this->assertEquals($dateTimeImmutable->getTimestamp(), $rep);
		$this->assertTrue(is_string($rep));
	}

	/**
	 * Test mergeValue method with DateTime (should be cloned)
	 */
	public function testMergeValueWithDateTime() {
		$dateTime = new \DateTime('2024-01-15 14:30:00');
		$merged = $this->dateTimeProperty->mergeValue($dateTime, false, $this->mockMergeOperation);
		
		$this->assertInstanceOf(\DateTime::class, $merged);
		$this->assertNotSame($dateTime, $merged); // Should be cloned
		$this->assertEquals($dateTime->format('Y-m-d H:i:s'), $merged->format('Y-m-d H:i:s'));
	}

	/**
	 * Test mergeValue method with DateTimeImmutable (should NOT be cloned)
	 */
	public function testMergeValueWithDateTimeImmutable() {
		$dateTimeImmutable = new \DateTimeImmutable('2024-01-15 14:30:00');
		$merged = $this->dateTimeProperty->mergeValue($dateTimeImmutable, false, $this->mockMergeOperation);
		
		$this->assertInstanceOf(\DateTimeImmutable::class, $merged);
		$this->assertSame($dateTimeImmutable, $merged); // Should be the same object
	}

	/**
	 * Test mergeValue with same entity (should return as-is)
	 */
	public function testMergeValueSameEntity() {
		$dateTime = new \DateTime('2024-01-15 14:30:00');
		$merged = $this->dateTimeProperty->mergeValue($dateTime, true, $this->mockMergeOperation);
		
		$this->assertSame($dateTime, $merged); // Should be the same object for same entity
	}

	/**
	 * Test mergeValue with null value
	 */
	public function testMergeValueWithNull() {
		$merged = $this->dateTimeProperty->mergeValue(null, false, $this->mockMergeOperation);
		
		$this->assertNull($merged);
	}

	/**
	 * Test createValueHash method
	 */
	public function testCreateValueHash() {
		$dateTime = new \DateTime('2024-01-15 14:30:00');
		$valueHash = $this->dateTimeProperty->createValueHash($dateTime, $this->mockMagicContext);
		
		$this->assertInstanceOf(CommonValueHash::class, $valueHash);
		
		// Test with null
		$nullValueHash = $this->dateTimeProperty->createValueHash(null, $this->mockMagicContext);
		$this->assertInstanceOf(CommonValueHash::class, $nullValueHash);
	}

	/**
	 * Test repToValue method
	 */
	public function testRepToValue() {
		$timestamp = '1705329000'; // 2024-01-15 14:30:00 UTC
		$dateTime = $this->dateTimeProperty->repToValue($timestamp);
		
		$this->assertInstanceOf(\DateTime::class, $dateTime);
		$this->assertEquals($timestamp, $dateTime->getTimestamp());
	}

	/**
	 * Test with DateUtils compatibility
	 */
	public function testDateUtilsCompatibility() {
		$dateTime = new \DateTime('2024-01-15 14:30:00');
		$dateTimeImmutable = new \DateTimeImmutable('2024-01-15 14:30:00');
		
		// Test formatDateTime
		$formatted1 = DateUtils::formatDateTime($dateTime, 'Y-m-d H:i:s');
		$formatted2 = DateUtils::formatDateTime($dateTimeImmutable, 'Y-m-d H:i:s');
		
		$this->assertEquals('2024-01-15 14:30:00', $formatted1);
		$this->assertEquals('2024-01-15 14:30:00', $formatted2);
		
		// Test dateTimeToIso
		$iso1 = DateUtils::dateTimeToIso($dateTime);
		$iso2 = DateUtils::dateTimeToIso($dateTimeImmutable);
		
		$this->assertIsString($iso1);
		$this->assertIsString($iso2);
		
		// Test dateTimeToSql
		$sql1 = DateUtils::dateTimeToSql($dateTime);
		$sql2 = DateUtils::dateTimeToSql($dateTimeImmutable);
		
		$this->assertEquals('2024-01-15 14:30:00', $sql1);
		$this->assertEquals('2024-01-15 14:30:00', $sql2);
	}

	/**
	 * Test that type validation accepts both DateTime and DateTimeImmutable
	 */
	public function testTypeValidation() {
		$dateTime = new \DateTime();
		$dateTimeImmutable = new \DateTimeImmutable();
		
		// These should not throw exceptions
		$this->dateTimeProperty->valueToRep($dateTime);
		$this->dateTimeProperty->valueToRep($dateTimeImmutable);
		
		$this->dateTimeProperty->mergeValue($dateTime, false, $this->mockMergeOperation);
		$this->dateTimeProperty->mergeValue($dateTimeImmutable, false, $this->mockMergeOperation);
		
		$this->expectNotToPerformAssertions();
	}

	/**
	 * Test invalid type validation
	 */
	public function testInvalidTypeValidation() {
		$this->expectException(\InvalidArgumentException::class);
		$this->dateTimeProperty->valueToRep("not a datetime");
	}
} 