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
use n2n\persistence\orm\attribute\Embedded;
use n2n\impl\persistence\orm\property\mock\EmbeddedMock;
use n2n\impl\persistence\orm\property\mock\NoTypeManyToOneMock;
use n2n\util\ex\err\ConfigurationError;
use n2n\impl\persistence\orm\property\mock\TargetEntityNotFoundMock;
use n2n\impl\persistence\orm\property\mock\RelationUnionErrorMock;
use n2n\impl\persistence\orm\property\mock\RelationUnregisteredErrorMock;

class CommonEntityPropertyProviderErrorTest extends TestCase {

	public function setUp(): void {

	}

	function testRelationUnionTypeError() {
		$emm = new EntityModelManager([RelationUnionErrorMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));

		try {
			$emm->getEntityModelByClass(RelationUnionErrorMock::class);
			$this->fail();
		} catch (ConfigurationError $e) {
			$this->assertEquals(
					'TargetEntity not declared or not recognizable for: n2n\impl\persistence\orm\property\mock\RelationUnionErrorMock::$oneToOne',
					$e->getMessage());
			$this->assertEquals(30, $e->getLine());
		}
	}

	function testRelationUnregisteredError() {
		$emm = new EntityModelManager([RelationUnregisteredErrorMock::class],
				new EntityModelFactory([CommonEntityPropertyProvider::class]));

		try {
			$emm->getEntityModelByClass(RelationUnregisteredErrorMock::class);
			$this->fail();
		} catch (ConfigurationError $e) {
			$this->assertEquals(30, $e->getLine());
		}
	}

}
