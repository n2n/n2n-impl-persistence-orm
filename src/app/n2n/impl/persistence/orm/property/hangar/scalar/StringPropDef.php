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
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\persistence\orm\property\EntityProperty;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use n2n\util\type\ArgUtils;
use n2n\persistence\meta\structure\common\CommonStringColumn;
use phpbob\representation\PhpTypeDef;
use n2n\web\dispatch\mag\MagCollection;
use n2n\persistence\meta\structure\Column;
use hangar\api\CompatibilityLevel;

class StringPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_LENGTH = 'length';
	const PROP_NAME_CHARSET = 'charset';
	
	public function getName(): string {
		return 'String';
	}

	public function createMagCollection(PropSourceDef $propSourceDef = null): MagCollection {
		$optionCollection = new MagCollection();
	
		$length = $this->columnDefaults->getDefaultStringLength();
		$charset = $this->columnDefaults->getDefaultStringCharset();
		if (null !== $propSourceDef) {
			$length = $propSourceDef->getHangarData()->get(self::PROP_NAME_LENGTH, false, $length);
			$charset = $propSourceDef->getHangarData()->get(self::PROP_NAME_CHARSET, false, $charset);
		}
		
		$optionCollection->addMag(self::PROP_NAME_LENGTH, new NumericMag('Length', $length));
		$optionCollection->addMag(self::PROP_NAME_CHARSET, new StringMag('Charset', $charset));
	
		return $optionCollection;
	}
	
	public function updatePropSourceDef(Attributes $attributes, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll(
				array(self::PROP_NAME_LENGTH => $attributes->get(self::PROP_NAME_LENGTH, false, $this->columnDefaults->getDefaultStringLength()),
						self::PROP_NAME_CHARSET => $attributes->get(self::PROP_NAME_CHARSET, false, $this->columnDefaults->getDefaultStringCharset())));
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('string'));
	}
	
	public function testCompatibility(PropSourceDef $propSourceDef): int {
		if (null === $propSourceDef->getPhpTypeDef() || $propSourceDef->getPhpTypeDef()->isString()) {
			if (null !== $propSourceDef->getPhpTypeDef()) {
				return CompatibilityLevel::COMMON;
			}
			
			return 2;
		}
		
		return CompatibilityLevel::NOT_COMPATIBLE;
	}
	
	/**
	 * @param PropSourceDef $propSourceDef
	 * @return \n2n\persistence\meta\structure\Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): Column {
		ArgUtils::assertTrue($entityProperty instanceof ScalarEntityProperty);
	
		return new CommonStringColumn($entityProperty->getColumnName(),
				$this->determineLength($propSourceDef->getHangarData()),
				$this->determineCharset($propSourceDef->getHangarData()));
	}
	
	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, ColumnFactory $columnFactory, $columnName, Attributes $attributes) {
		$columnFactory->createStringColumn($columnName, $this->determineLength($attributes), 
				$this->determineCharset($attributes));
	}
	
	private function determineLength(Attributes $attributes) {
		return $attributes->get(self::PROP_NAME_LENGTH, 
				false, $this->columnDefaults->getDefaultStringLength());
	}
	
	private function determineCharset(Attributes $attributes) {
		return $attributes->get(self::PROP_NAME_CHARSET, 
				false, $this->columnDefaults->getDefaultStringCharset());
	}
}
