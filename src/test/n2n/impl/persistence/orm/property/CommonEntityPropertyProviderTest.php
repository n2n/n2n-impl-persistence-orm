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

use n2n\persistence\orm\model\EntityModelManager;
use n2n\io\orm\ManagedFileEntityProperty;
use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\property\mock\EntityPropertiesMock;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\mock\TargetMock;
use n2n\io\managed\FileManager;
use n2n\impl\persistence\orm\property\mock\EmbeddedMock;
use n2n\impl\persistence\orm\property\mock\NoTypeManyToOneMock;
use n2n\util\ex\err\ConfigurationError;
use n2n\impl\persistence\orm\property\mock\TargetEntityNotFoundMock;
use n2n\impl\persistence\orm\property\relation\JoinTableToOneRelation;
use n2n\impl\persistence\orm\property\relation\JoinColumnToOneRelation;
use n2n\impl\persistence\orm\property\relation\JoinTableToManyRelation;

class CommonEntityPropertyProviderTest extends TestCase {
	private EntityModelManager $emm;

	public function setUp(): void {
		$this->emm = new EntityModelManager([EntityPropertiesMock::class, TargetMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));
	}

	function testPropertyAttributesSet() {
		$entityModel = $this->emm->getEntityModelByClass(EntityPropertiesMock::class);

		$idEp = $entityModel->getLevelEntityPropertyByName('id');
		$this->assertInstanceOf(IntEntityProperty::class, $idEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('id'));
		$idEp = $entityModel->getLevelEntityPropertyByName('id');
		$this->assertInstanceOf(IntEntityProperty::class, $idEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('associationOverrides'));
		$associationOverridesEp = $entityModel->getLevelEntityPropertyByName('associationOverrides');
		$this->assertInstanceOf(ScalarEntityProperty::class, $associationOverridesEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('attributeOverrides'));
		$attributeOverrides = $entityModel->getLevelEntityPropertyByName('attributeOverrides');
		$this->assertInstanceOf(ScalarEntityProperty::class, $attributeOverrides);

		$this->assertTrue($entityModel->containsEntityPropertyName('column'));
		$columnEp = $entityModel->getLevelEntityPropertyByName('column');
		$this->assertInstanceOf(StringEntityProperty::class, $columnEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('dateTime'));
		$dateTimeEp = $entityModel->getLevelEntityPropertyByName('dateTime');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $dateTimeEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('embedded'));
		$embeddedEp = $entityModel->getLevelEntityPropertyByName('embedded');
		$this->assertInstanceOf(EmbeddedEntityProperty::class, $embeddedEp);
		$this->assertEquals(EmbeddedMock::class, $embeddedEp->getTargetClass()->getName());

		$this->assertTrue($entityModel->containsEntityPropertyName('entityListeners'));
		$entityListenersEp = $entityModel->getLevelEntityPropertyByName('entityListeners');
		$this->assertInstanceOf(ScalarEntityProperty::class, $entityListenersEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('managedFile'));
		$managedFileEp = $entityModel->getLevelEntityPropertyByName('managedFile');
		$this->assertInstanceOf(ManagedFileEntityProperty::class, $managedFileEp);
		$this->assertEquals(FileManager::TYPE_PRIVATE, $managedFileEp->getFileManagerClassName());

		$this->assertTrue($entityModel->containsEntityPropertyName('manyToMany'));
		$manyToManyEp = $entityModel->getLevelEntityPropertyByName('manyToMany');
		$this->assertInstanceOf(ToManyEntityProperty::class, $manyToManyEp);
		$this->assertEquals($manyToManyEp->getTargetEntityModel()->getClass()->getName(), TargetMock::class);

		$this->assertTrue($entityModel->containsEntityPropertyName('manyToOne'));
		$manyToOneEp = $entityModel->getLevelEntityPropertyByName('manyToOne');
		$this->assertInstanceOf(ToOneEntityProperty::class, $manyToOneEp);
		$this->assertEquals($manyToOneEp->getTargetEntityModel()->getClass()->getName(), TargetMock::class);

		$this->assertTrue($entityModel->containsEntityPropertyName('oneToOne'));
		$oneToOneEp = $entityModel->getLevelEntityPropertyByName('oneToOne');
		$this->assertInstanceOf(ToOneEntityProperty::class, $oneToOneEp);
		$this->assertEquals($oneToOneEp->getTargetEntityModel()->getClass()->getName(), TargetMock::class);

		$this->assertTrue($entityModel->containsEntityPropertyName('oneToMany'));
		$oneToManyEp = $entityModel->getLevelEntityPropertyByName('oneToMany');
		$this->assertInstanceOf(ToManyEntityProperty::class, $oneToManyEp);
		$this->assertEquals($oneToManyEp->getTargetEntityModel()->getClass()->getName(), TargetMock::class);

		$this->assertTrue($entityModel->containsEntityPropertyName('n2nLocale'));
		$n2nLocaleEp = $entityModel->getLevelEntityPropertyByName('n2nLocale');
		$this->assertInstanceOf(N2nLocaleEntityProperty::class, $n2nLocaleEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('orderBy'));
		$orderByEp = $entityModel->getLevelEntityPropertyByName('orderBy');
		$this->assertInstanceOf(ScalarEntityProperty::class, $orderByEp);

		$this->assertFalse($entityModel->containsEntityPropertyName('transient'));

		$this->assertTrue($entityModel->containsEntityPropertyName('url'));
		$urlEp = $entityModel->getLevelEntityPropertyByName('url');
		$this->assertInstanceOf(UrlEntityProperty::class, $urlEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('joinTable'));
		$joinTableEp = $entityModel->getLevelEntityPropertyByName('joinTable');
		$this->assertInstanceOf(ToOneEntityProperty::class, $joinTableEp);

		$relation = $joinTableEp->getRelation();
		$this->assertInstanceOf(JoinTableToOneRelation::class, $relation);
		$this->assertEquals($relation->getJoinTableName(), 'join_table');
		$this->assertEquals($relation->getJoinColumnName(), 'persistence_test_class_id');
		$this->assertEquals($relation->getInverseJoinColumnName(), 'test_id');

		$this->assertTrue($entityModel->containsEntityPropertyName('joinColumn'));
		$joinColumnEp = $entityModel->getLevelEntityPropertyByName('joinColumn');
		$this->assertInstanceOf(ToOneEntityProperty::class, $joinColumnEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('joinTables'));
		$joinTablesEp = $entityModel->getLevelEntityPropertyByName('joinTables');
		$this->assertInstanceOf(ToManyEntityProperty::class, $joinTablesEp);
		$joinTablesEp->getRelation();

		$relation = $joinTablesEp->getRelation();
		$this->assertInstanceOf(JoinTableToManyRelation::class, $relation);
		$this->assertEquals($relation->getJoinColumnName(), 'table_ids');

		$relation = $joinColumnEp->getRelation();
		$this->assertInstanceOf(JoinColumnToOneRelation::class, $relation);
		$this->assertEquals($relation->getJoinColumnName(), 'join_column');
	}

	function testNoTypeManyToOneMock() {
		$this->emm = new EntityModelManager([NoTypeManyToOneMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));
		$this->expectException(ConfigurationError::class);
		$this->emm->getEntityModelByClass(NoTypeManyToOneMock::class)->ensureInit();
	}

	function testTypeNotFoundMock() {
		$this->emm = new EntityModelManager([TargetEntityNotFoundMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));
		$this->expectException(ConfigurationError::class);

		$this->emm->getEntityModelByClass(TargetEntityNotFoundMock::class)->ensureInit();
	}
}
