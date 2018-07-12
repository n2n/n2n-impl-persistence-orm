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

use hangar\api\HangarPropDef;
use hangar\api\PropSourceDef;
use n2n\util\config\Attributes;
use hangar\api\DbInfo;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\persistence\orm\annotation\AnnoManyToMany;
use n2n\reflection\ArgUtils;
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use n2n\reflection\CastUtils;
use n2n\impl\persistence\orm\property\relation\JoinTableToManyRelation;
use n2n\persistence\meta\structure\IndexType;
use hangar\api\ColumnDefaults;
use n2n\impl\persistence\orm\property\ToManyEntityProperty;
use hangar\api\CompatibilityLevel;
use hangar\core\option\OrmRelationMagCollection;
use phpbob\PhpbobUtils;

class ManyToManyPropDef implements HangarPropDef {
	protected $columnDefaults;
	
	public function setup(ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
	}
	
	public function getName() {
		return 'ManyToMany';
	}

	public function getEntityPropertyClass() {
		return new \ReflectionClass('n2n\impl\persistence\orm\property\ToManyEntityProperty');
	}

	public function createMagCollection(PropSourceDef $propSourceDef = null) {
		$magCollection = new OrmRelationMagCollection();
		
		if (null !== $propSourceDef) {
			$propertyAnnoCollection = $propSourceDef->getPhpProperty()->getPhpPropertyAnnoCollection();
			if ($propertyAnnoCollection->hasPhpAnno(AnnoManyToMany::class)) {
				$phpAnnotation = $propertyAnnoCollection->getPhpAnno(AnnoManyToMany::class);
				if (null !== $phpAnnotation &&
						null !== $annotManyToMany = $phpAnnotation->determineAnnotation()) {
					CastUtils::assertTrue($annotManyToMany instanceof AnnoManyToMany);
					$magCollection->setValuesByAnnotation($annotManyToMany);
				}
			}
		}
		
		return $magCollection;
	}

	public function updatePropSourceDef(Attributes $attributes, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll($attributes->toArray());
		
		$targetEntityTypeName = $attributes->get(OrmRelationMagCollection::PROP_NAME_TARGET_ENTITY_CLASS);
		//$propSourceDef->setReturnTypeName($targetEntityTypeName . ' []');
		$propSourceDef->setPhpTypeDef(null);
		
		$phpProperty = $propSourceDef->getPhpProperty();
		$propertyAnnoCollection = $phpProperty->getPhpPropertyAnnoCollection();
		$anno = $propertyAnnoCollection->getOrCreatePhpAnno(AnnoManyToMany::class);
		$anno->resetPhpAnnoParams();
		$anno->createPhpAnnoParam(PhpbobUtils::extractClassName($targetEntityTypeName) . '::getClass()');
		$phpProperty->createPhpUse($targetEntityTypeName);
		
		$cascadeTypeValue = OrmRelationMagCollection::buildCascadeTypeAnnoParam(
				$attributes->get(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE));
		
		$fetchType = OrmRelationMagCollection::buildFetchTypeAnnoParam(
				$attributes->getString(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE));
		
		if (null !== ($mappedBy = $attributes->get(OrmRelationMagCollection::PROP_NAME_MAPPED_BY))) {
			$anno->createPhpAnnoParam($mappedBy, true);
		} else {
			if (null !== $cascadeTypeValue || null !== $fetchType) {
				$anno->createPhpAnnoParam('null');
			}
		}
		
		if (null !== $cascadeTypeValue) {
			$anno->createPhpAnnoParam($cascadeTypeValue);
		} else if (null !== $fetchType) {
			$anno->createPhpAnnoParam('null');
		}
		
		if (null !== $fetchType) {
			$anno->createPhpAnnoParam($fetchType);
		}
	}

	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, 
			EntityProperty $entityProperty, AnnotationSet $as) {
		
		ArgUtils::assertTrue($entityProperty instanceof RelationEntityProperty);
		
		$propertyName = $propSourceDef->getPropertyName();
		$annoManyToMany = $as->getPropertyAnnotation($propertyName, AnnoManyToMany::class);
		CastUtils::assertTrue($annoManyToMany instanceof AnnoManyToMany);
		
		if (null === $annoManyToMany->getMappedBy()) {
			$relation = $entityProperty->getRelation();
			if ($relation instanceof JoinTableToManyRelation) {
				$joinTableName = $relation->getJoinTableName();
				$joinColumnName = $relation->getJoinColumnName();
				$inverseJoinColumnName = $relation->getInverseJoinColumnName();
				
				$database = $dbInfo->getDatabase();
				if ($database->containsMetaEntityName($joinTableName)) {
					$database->removeMetaEntityByName($joinTableName);
				}
				
				$table = $database->createMetaEntityFactory()->createTable($joinTableName);
				$columnFactory = $table->createColumnFactory();
				//@todo id column defs from hangar
				$columnFactory->createIntegerColumn($joinColumnName, 
						$this->columnDefaults->getDefaultIntegerSize(), $this->columnDefaults->getDefaultInterSigned());
				$columnFactory->createIntegerColumn($inverseJoinColumnName, $this->columnDefaults->getDefaultIntegerSize(), 
						$this->columnDefaults->getDefaultInterSigned());
				$table->createIndex(IndexType::PRIMARY, array($joinColumnName, $inverseJoinColumnName));
			}
		}
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
		if ($entityProperty instanceof ToManyEntityProperty 
				&& $entityProperty->getType() == RelationEntityProperty::TYPE_MANY_TO_MANY) {
			return CompatibilityLevel::COMMON;
		}
	
		return CompatibilityLevel::NOT_COMPATIBLE;
	}

	/**
	 * {@inheritDoc}
	 * @see \hangar\api\HangarPropDef::resetPropSourceDef()
	 */
	public function resetPropSourceDef(PropSourceDef $propSourceDef) {
		$phpProperty = $propSourceDef->getPhpProperty();
		$phpPropertyAnnoCollection = $phpProperty->getPhpPropertyAnnoCollection();
		if ($phpPropertyAnnoCollection->hasPhpAnno(AnnoManyToMany::class)) {
			$phpAnno = $phpPropertyAnnoCollection->getPhpAnno(AnnoManyToMany::class);
			if (null !== ($annoManyToMany = $phpAnno->determineAnnotation())) {
				CastUtils::assertTrue($annoManyToMany instanceof AnnoManyToMany);
				$phpProperty->removePhpUse($annoManyToMany->getTargetEntityClass()->getName());		
			}
			
			//@todo try to findout TargetClassName without Annotation
			
			$phpPropertyAnnoCollection->removePhpAnno(AnnoManyToMany::class);
			$phpProperty->removePhpUse(AnnoManyToMany::class);
		}
	}
}
