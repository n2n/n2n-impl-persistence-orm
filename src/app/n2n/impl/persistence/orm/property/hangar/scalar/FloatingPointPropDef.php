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
namespace n2n\impl\persistence\orm\property\hangar\scalar;

use n2n\util\type\attrs\Attributes;
use hangar\api\DbInfo;
use n2n\persistence\meta\structure\ColumnFactory;
use hangar\api\PropSourceDef;
use n2n\impl\web\dispatch\mag\model\NumericMag;
use n2n\persistence\orm\property\EntityProperty;
use n2n\util\type\ArgUtils;
use n2n\persistence\meta\structure\common\CommonFloatingPointColumn;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use phpbob\representation\PhpTypeDef;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\meta\structure\Column;
use phpbob\Phpbob;
use hangar\api\CompatibilityLevel;

class FloatingPointPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_SIZE = 'size';
	
	public function getName(): string {
		return 'Floating Point';
	}
	
	public function testSourceCompatibility(PropSourceDef $propSourceDef): int {
		if (null === $propSourceDef->getPhpTypeDef() || $propSourceDef->getPhpTypeDef()->isFloat()) {
			return parent::testSourceCompatibility($propSourceDef);
		}
		
		return CompatibilityLevel::NOT_COMPATIBLE;
		
	}
	
	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$optionCollection = new MagCollection();
		
		$size = $this->columnDefaults->getDefaultFloatingPointSize();
		if (null !== $propSourceDef) {
			$size = $propSourceDef->getHangarData()->get(self::PROP_NAME_SIZE, false, $size);
		}
		
		$optionCollection->addMag(self::PROP_NAME_SIZE, new NumericMag('Size', $size, true));
		
		return $optionCollection;
	}

	public function updatePropSourceDef(Attributes $attributes, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll(array(self::PROP_NAME_SIZE => 
				$attributes->get(self::PROP_NAME_SIZE)));
		$propSourceDef->setPhpTypeDef(new PhpTypeDef(Phpbob::TYPE_FLOAT));
	}

	/**
	 * @param PropSourceDef $propSourceDef
	 * @return \n2n\persistence\meta\structure\Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): Column {
		ArgUtils::assertTrue($entityProperty instanceof ScalarEntityProperty);
	
		return new CommonFloatingPointColumn($entityProperty->getColumnName(),
				$this->determineSize($propSourceDef->getHangarData()));
	}

	protected function createColumn(EntityProperty $entityProperty, 
			DbInfo $dbInfo, ColumnFactory $columnFactory, $columnName, Attributes $attributes) {
		$columnFactory->createFloatingPointColumn($columnName, $this->determineSize($attributes));
	}
	
	private function determineSize(Attributes $attributes) {
		return $attributes->get(self::PROP_NAME_SIZE, false, 
				$this->columnDefaults->getDefaultFloatingPointSize());
	}
}
