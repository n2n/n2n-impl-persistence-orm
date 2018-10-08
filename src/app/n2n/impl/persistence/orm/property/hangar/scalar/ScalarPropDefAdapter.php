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

use hangar\api\HangarPropDef;
use n2n\util\config\Attributes;
use hangar\api\PropSourceDef;
use hangar\api\DbInfo;
use n2n\persistence\meta\structure\ColumnFactory;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\reflection\ArgUtils;
use n2n\impl\persistence\orm\property\ScalarEntityProperty;
use hangar\api\ColumnDefaults;
use hangar\api\CompatibilityLevel;
use hangar\api\HuoContext;

abstract class ScalarPropDefAdapter implements HangarPropDef {
	
	protected $columnDefaults;
	protected $huoContext;
	
	public function setup(HuoContext $huoContext, ColumnDefaults $columnDefaults) {
		$this->huoContext = $huoContext;
		$this->columnDefaults = $columnDefaults;
	}
	
	public function getEntityPropertyClass(): \ReflectionClass {
		return new \ReflectionClass('n2n\impl\persistence\orm\property\ScalarEntityProperty');
	}
	
	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty,
			AnnotationSet $annotationSet) {
		
		ArgUtils::assertTrue($entityProperty instanceof ScalarEntityProperty);
				
		$columnName = $entityProperty->getColumnName();
		$dbInfo->removeColumn($columnName);
		$columnFactory = $dbInfo->getTable()->createColumnFactory();
		$attributes = $propSourceDef->getHangarData();
	
		$this->createColumn($entityProperty, $dbInfo, $columnFactory, $columnName, $attributes);
	}

	/**
	 * @param EntityProperty $entityProperty
	 * @return int
	 */
	public function testCompatibility(EntityProperty $entityProperty): int {
		if ($entityProperty instanceof ScalarEntityProperty) {
			return CompatibilityLevel::COMPATIBLE;
		}
	
		return CompatibilityLevel::NOT_COMPATIBLE;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
	    
	}
	
	protected function createColumn(EntityProperty $entityProperty, DbInfo $dbInfo, ColumnFactory $columnFactory, 
			$columnName, Attributes $attributes) {}
	
}
