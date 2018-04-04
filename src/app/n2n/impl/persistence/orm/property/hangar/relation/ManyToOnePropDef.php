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
namespace n2n\impl\persistence\orm\property\hangar\relation;

use hangar\entity\model\HangarPropDef;
use hangar\entity\model\PropSourceDef;
use n2n\util\config\Attributes;
use hangar\entity\model\DbInfo;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\reflection\ArgUtils;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\impl\persistence\orm\property\ToOneEntityProperty;
use hangar\core\config\ColumnDefaults;
use n2n\impl\persistence\orm\property\relation\JoinColumnToOneRelation;
use n2n\persistence\meta\structure\IndexType;
use n2n\persistence\meta\structure\Table;
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use hangar\entity\model\CompatibilityLevel;
use hangar\core\option\OrmRelationMagCollection;
use n2n\reflection\CastUtils;

class ManyToOnePropDef implements HangarPropDef {
	protected $columnDefaults;
	
	public function setup(ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
	}
	
	public function getName() {
		return 'ManyToOne';
	}

	public function getEntityPropertyClass() {
		return new \ReflectionClass(ManyToOnePropDef::class);
	}

	public function createMagCollection(PropSourceDef $propSourceDef = null) {
		$magCollection = new OrmRelationMagCollection();
		
		if (null !== $propSourceDef) {
			$phpAnnotation = $propSourceDef->getPhpPropertyAnno()->getParam(AnnoManyToOne::class);
			if (null !== $phpAnnotation) {
				$annotManyToOne = $phpAnnotation->getAnnotation();
				CastUtils::assertTrue($annotManyToOne instanceof AnnoManyToOne);
				$magCollection->setValuesByAnnotation($annotManyToOne);
			}
		}
		
		return $magCollection;
	}

	public function updatePropSourceDef(Attributes $attributes, PropSourceDef $propSourceDef) {
		$propSourceDef->setBoolean(false);
		$propSourceDef->getHangarData()->setAll($attributes->toArray());
		
		$targetEntityTypeName = $attributes->get(OrmRelationMagCollection::PROP_NAME_TARGET_ENTITY_CLASS);
		
		$propSourceDef->setReturnTypeName($targetEntityTypeName);
		$propSourceDef->setSetterTypeName($targetEntityTypeName);
		
		$propertyAnno = $propSourceDef->getPhpPropertyAnno();
		
		$annoParam = $propertyAnno->getOrCreateParam(AnnoManyToOne::class);
		$annoParam->setConstructorParams(array());
		$annoParam->addConstructorParam($targetEntityTypeName . '::getClass()');
		
		$cascadeTypeValue = OrmRelationMagCollection::buildCascadeTypeAnnoParam(
				$attributes->get(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE));
		
		$fetchType = OrmRelationMagCollection::buildFetchTypeAnnoParam(
				$attributes->getString(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE));

		// Pseudo mapped by
		if (null !== $cascadeTypeValue) {
			$annoParam->addConstructorParam($cascadeTypeValue);
		} elseif (null !== $fetchType) {
			$annoParam->addConstructorParam('null');
		}
		
		if (null !== $fetchType) {
			$annoParam->addConstructorParam($fetchType);
		}
	}

	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, EntityProperty $entityProperty, 
			AnnotationSet $annotationSet) {
		ArgUtils::assertTrue($entityProperty instanceof ToOneEntityProperty);
		
		$relation = $entityProperty->getRelation();
		ArgUtils::assertTrue($relation instanceof JoinColumnToOneRelation);
		
		$joinColumnName = $relation->getJoinColumnName(); 
		$dbInfo->getTable()->createColumnFactory()->createIntegerColumn($joinColumnName, 
				$this->columnDefaults->getDefaultIntegerSize(), $this->columnDefaults->getDefaultInterSigned());
		if (!$this->hasIndexForColumn($dbInfo->getTable(), $joinColumnName)) {
			$dbInfo->getTable()->createIndex(IndexType::INDEX, array($joinColumnName));
		}
	}
	
	private function hasIndexForColumn(Table $table, $columnName) {
		foreach ($table->getIndexes() as $index) {
			if ($index->containsColumnName($columnName) && count($index->getColumns()) === 1) return true;
		}
		
		return false;
	}

	/**
	 * @param PropSourceDef $propSourceDef
	 * @return \n2n\persistence\meta\structure\Column
	 */
	public function createMetaColumn(EntityProperty $entityProperty, PropSourceDef $propSourceDef) {
		return null;
	}
	
	/**
	 * @param EntityProperty $entityProperty
	 * @return int
	 */
	public function testCompatibility(EntityProperty $entityProperty) {
		if ($entityProperty instanceof ToOneEntityProperty
				&& $entityProperty->getType() == RelationEntityProperty::TYPE_MANY_TO_ONE) {
			return CompatibilityLevel::COMMON;
		}

		return CompatibilityLevel::NOT_COMPATIBLE;
	}
}
