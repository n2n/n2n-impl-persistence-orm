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

use n2n\util\type\attrs\DataSet;
use hangar\api\DbInfo;
use n2n\spec\dbo\meta\structure\ColumnFactory;
use n2n\impl\web\dispatch\mag\model\NumericMag;
use hangar\api\PropSourceDef;
use n2n\persistence\meta\structure\common\CommonBinaryColumn;
use n2n\persistence\orm\property\EntityProperty;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use phpbob\representation\PhpTypeDef;
use n2n\web\dispatch\mag\MagCollection;
use n2n\spec\dbo\meta\structure\Column;
use hangar\api\CompatibilityLevel;

class BinaryPropDef extends ScalarPropDefAdapter {
	const PROP_NAME_SIZE = 'size';
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::getName()
	 */
	public function getName(): string {
		return 'Binary'; 
	}

	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, ColumnFactory $columnFactory, $columnName, 
			DataSet $dataSet) {
		$columnFactory->createBinaryColumn($columnName, 
				$dataSet->get(self::PROP_NAME_SIZE, false, $this->columnDefaults->getDefaultBinarySize()));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::createMagCollection()
	 * @return MagCollection
	 */
	public function createMagCollection(?PropSourceDef $propSourceDef = null): MagCollection {
		$optionCollection = new MagCollection();
		
		$size = $this->columnDefaults->getDefaultBinarySize();
		if (null !== $propSourceDef) {
			$size = $propSourceDef->getHangarData()->get(self::PROP_NAME_SIZE, false, $size);
		}
		
		$optionCollection->addMag(self::PROP_NAME_SIZE, new NumericMag('Size', $size, true));
		
		return $optionCollection;
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::updatePropSourceDef()
	 */
	public function updatePropSourceDef(DataSet $dataSet, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll(array(self::PROP_NAME_SIZE => 
				$dataSet->get(self::PROP_NAME_SIZE)));
		$propSourceDef->setPhpTypeDef(new PhpTypeDef('string'));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\impl\persistence\orm\property\hangar\scalar\ScalarPropDefAdapter::testSourceCompatibility()
	 */
	public function testSourceCompatibility(PropSourceDef $propSourceDef): int {
		if (null === $propSourceDef->getPhpTypeDef() || $propSourceDef->getPhpTypeDef()->isString()) {
			return CompatibilityLevel::COMPATIBLE;
		}
		
		return CompatibilityLevel::NOT_COMPATIBLE;
	}

	/**
	 * @param PropSourceDef $propSourceDef
	 * @return Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef): ?Column {
		ArgUtils::assertTrue($entityProperty instanceof ScalarEntityProperty);
		
		return new CommonBinaryColumn($entityProperty->getColumnName(), 
				$this->determineSize($propSourceDef->getHangarData()));
	}
	
	private function determineSize(DataSet $dataSet) {
		return $dataSet->optInt(self::PROP_NAME_SIZE,  $this->columnDefaults->getDefaultBinarySize());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::isBasic()
	 */
	public function isBasic(): bool {
		return false;
	}
}
