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

use n2n\persistence\orm\property\EntityPropertyProvider;
use n2n\reflection\property\AccessProxy;
use n2n\persistence\orm\property\ClassSetup;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\impl\persistence\orm\property\relation\RelationFactory;
use n2n\persistence\orm\annotation\AnnoManyToMany;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\ReflectionUtils;
use n2n\persistence\orm\model\NamingStrategy;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\annotation\AnnoEmbedded;
use n2n\io\orm\FileEntityProperty;
use n2n\io\orm\ManagedFileEntityProperty;
use n2n\io\managed\impl\SimpleFileLocator;
use n2n\util\io\IoUtils;
use n2n\util\uri\Url;
use n2n\persistence\orm\annotation\AnnoBool;
use n2n\util\type\TypeName;
use n2n\reflection\ReflectionContext;
use n2n\persistence\orm\attribute\DateTime;
use n2n\persistence\orm\attribute\N2nLocale;
use n2n\persistence\orm\attribute\File;
use n2n\persistence\orm\attribute\ManagedFile;
use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\property\test\PersistenceTestClass;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\test\TargetClassTest;

class CommonEntityPropertyProviderTest extends TestCase {
	private EntityModelManager $emm;

	public function setUp(): void {
		$this->emm = new EntityModelManager([PersistenceTestClass::class, TargetClassTest::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));
	}

	function testHoleradio() {
		$entityModel = $this->emm->getEntityModelByClass(PersistenceTestClass::class);

		$this->assertTrue($entityModel->containsEntityPropertyName('id'));
		$idEp = $entityModel->getLevelEntityPropertyByName('id');
		$this->assertInstanceOf(ScalarEntityProperty::class, $idEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('associationOverrides'));
//		$associationOverridesEp = $entityModel->getLevelEntityPropertyByName('associationOverrides');
//		$this->assertInstanceOf(ScalarEntityProperty::class, $associationOverridesEp);
//

		$this->assertTrue($entityModel->containsEntityPropertyName('attributeOverrides'));
//		$attributeOverrides = $entityModel->getLevelEntityPropertyByName('attributeOverrides');
//		$this->assertInstanceOf(ScalarEntityProperty::class, $dateTimeEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('column'));
		$columnEp = $entityModel->getLevelEntityPropertyByName('column');
		$this->assertInstanceOf(ScalarEntityProperty::class, $columnEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('dateTime'));
		$dateTimeEp = $entityModel->getLevelEntityPropertyByName('dateTime');
		$this->assertInstanceOf(DateTimeEntityProperty::class, $dateTimeEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('discriminatorColumn'));
		$discColumnEp = $entityModel->getLevelEntityPropertyByName('discriminatorColumn');
		$this->assertInstanceOf(ScalarEntityProperty::class, $discColumnEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('discriminatorValue'));
		$discValEp = $entityModel->getLevelEntityPropertyByName('discriminatorValue');
		$this->assertInstanceOf(ScalarEntityProperty::class, $discValEp);

//		$this->assertTrue($entityModel->containsEntityPropertyName('embedded'));
//		$embeddedEp = $entityModel->getLevelEntityPropertyByName('embedded');
//		$this->assertInstanceOf(ScalarEntityProperty::class, $embeddedEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('entityListeners'));
		$entityListenersEp = $entityModel->getLevelEntityPropertyByName('entityListeners');
		$this->assertInstanceOf(ScalarEntityProperty::class, $entityListenersEp);

//		$this->assertTrue($entityModel->containsEntityPropertyName('file'));
//		$fileEp = $entityModel->getLevelEntityPropertyByName('file');
//		$this->assertInstanceOf(FileEntityProperty::class, $fileEp);

		$this->assertTrue($entityModel->containsEntityPropertyName('entityListeners'));
		$entityListenersEp = $entityModel->getLevelEntityPropertyByName('entityListeners');
		$this->assertInstanceOf(ScalarEntityProperty::class, $entityListenersEp);


//	#[DateTime, JoinColumn('holeradio_date_time')]
//	private $dateTime;
//
//
////	#[Embedded(TargetClassTest::class)]
////	private $embedded;
//
//	#[EntityListeners([EntityListener::class])]
//	private $entityListeners;
//	#[File]
//	private $file;
//
//	#[JoinTable('join_table', 'persistence_test_class_id')]
//	private $joinTable;
//	#[ManagedFile(FileManager::TYPE_PRIVATE)]
//	private $managedFile;
//	#[ManyToMany(TargetClassTest::class)]
//	private $manyToMany;
//	#[ManyToOne(TargetClassTest::class)]
//	private $manyToOne;
//	#[N2nLocale]
//	private $n2nLocale;
//	#[OneToMany(TargetClassTest::class)]
//	private $oneToMany;
//	#[OneToOne(TargetClassTest::class)]
//	private $oneToOne;
//	#[OrderBy(['orderIndex' => 'ASC'])]
//	private $orderBy;
//	#[Transient]
//	private $transient;
//	#[Url]
//	private $url;
	}
}
