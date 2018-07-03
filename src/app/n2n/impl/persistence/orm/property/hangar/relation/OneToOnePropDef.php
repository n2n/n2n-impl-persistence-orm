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
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use n2n\reflection\annotation\AnnotationSet;
use n2n\reflection\ArgUtils;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\impl\persistence\orm\property\ToOneEntityProperty;
use n2n\reflection\CastUtils;
use n2n\impl\persistence\orm\property\relation\ToOneRelation;
use n2n\impl\persistence\orm\property\relation\JoinColumnToOneRelation;
use hangar\api\ColumnDefaults;
use hangar\api\CompatibilityLevel;
use hangar\core\option\OrmRelationMagCollection;
use phpbob\representation\PhpTypeDef;
use phpbob\PhpbobUtils;

class OneToOnePropDef implements HangarPropDef {
	const PROP_NAME_PROPS = 'props';
	
	private $columnDefaults;
	
	public function setup(ColumnDefaults $columnDefaults) {
		$this->columnDefaults = $columnDefaults;
	}
	
	public function getName() {
		return 'OneToOne'; 
	}

	public function getEntityPropertyClass() {
		return new \ReflectionClass(ToOneEntityProperty::class);
	}

	public function createMagCollection(PropSourceDef $propSourceDef = null) {
		$magCollection = new OrmRelationMagCollection(true, true);
		
		if (null !== $propSourceDef) {
			$propertyAnnoCollection = $propSourceDef->getPhpProperty()->getPhpPropertyAnnoCollection();
			
			if ($propertyAnnoCollection->hasPhpAnno(AnnoOneToOne::class)) {
				$phpAnnotation = $propertyAnnoCollection->getPhpAnno(AnnoOneToOne::class);
				if (null !== $phpAnnotation && null !== ($oneToOne = $phpAnnotation->determineAnnotation())) {
					IllegalStateException::assertTrue($oneToOne instanceof AnnoOneToOne);
					$magCollection->setValuesByAnnotation($oneToOne);
				}
			} 
		}
		
		return $magCollection;
	}

	public function updatePropSourceDef(Attributes $attributes, PropSourceDef $propSourceDef) {
		$propSourceDef->getHangarData()->setAll($attributes->toArray());
		
		$targetEntityTypeName = $attributes->get(OrmRelationMagCollection::PROP_NAME_TARGET_ENTITY_CLASS);
		$propSourceDef->setPhpTypeDef(PhpTypeDef::fromTypeName($targetEntityTypeName));
		
		$phpProperty = $propSourceDef->getPhpProperty();
		$propertyAnnoCollection = $phpProperty->getPhpPropertyAnnoCollection();
		
		$anno = $propertyAnnoCollection->getOrCreatePhpAnno(AnnoOneToOne::class);
		$anno->resetPhpAnnoParams();
		$anno->createPhpAnnoParam(PhpbobUtils::extractClassName($targetEntityTypeName) . '::getClass()');
		$phpProperty->createPhpUse($targetEntityTypeName);
		
		$cascadeTypeValue = OrmRelationMagCollection::buildCascadeTypeAnnoParam(
				$attributes->get(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE));
		
		$fetchType = OrmRelationMagCollection::buildFetchTypeAnnoParam(
				$attributes->getString(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE));
		
		$orphanRemoval = $attributes->get(OrmRelationMagCollection::PROP_NAME_ORPHAN_REMOVAL);
		if (!$orphanRemoval) {
			$orphanRemoval = null;
		} else {
			$orphanRemoval = 'true';
		}
		
		if (null !== ($mappedBy = $attributes->get(OrmRelationMagCollection::PROP_NAME_MAPPED_BY))) {
			$anno->createPhpAnnoParam($mappedBy, true);
		} else {
			if (null !== $cascadeTypeValue || null !== $fetchType || null !== $orphanRemoval) {
				$anno->createPhpAnnoParam('null');
			}
		}
		
		if (null !== $cascadeTypeValue) {
			$anno->createPhpAnnoParam($cascadeTypeValue);
		} else if (null !== $fetchType || null !== $orphanRemoval) {
			$anno->createPhpAnnoParam('null');
		}
		
		if (null !== $fetchType) {
			$anno->createPhpAnnoParam($fetchType);
		} elseif (null !== $orphanRemoval) {
			$anno->createPhpAnnoParam('null');
		}
	
		if (null !== $orphanRemoval) {
			$anno->createPhpAnnoParam($orphanRemoval);
		}
	}

	public function applyDbMeta(DbInfo $dbInfo, PropSourceDef $propSourceDef, 
			EntityProperty $entityProperty, AnnotationSet $annotationSet) {
		ArgUtils::assertTrue($entityProperty instanceof RelationEntityProperty);
		
		$propertyName = $propSourceDef->getPropertyName();
		$annoOneToOne = $annotationSet->getPropertyAnnotation($propertyName, 
				AnnoOneToOne::class);
		CastUtils::assertTrue($annoOneToOne instanceof AnnoOneToOne);
		
		if (null === $annoOneToOne->getMappedBy()) {
			$relation = $entityProperty->getRelation();
			ArgUtils::assertTrue($relation instanceof ToOneRelation);
			
			if ($relation instanceof JoinColumnToOneRelation) {
				$dbInfo->getTable()->createColumnFactory()->createIntegerColumn(
						$relation->getJoinColumnName(), 
						$this->columnDefaults->getDefaultIntegerSize(), 
						$this->columnDefaults->getDefaultInterSigned());
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
		if ($entityProperty instanceof ToOneEntityProperty
				&& $entityProperty->getType() == RelationEntityProperty::TYPE_ONE_TO_ONE) {
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
		
		if ($phpPropertyAnnoCollection->hasPhpAnno(AnnoOneToOne::class)) {
			$phpAnno = $phpPropertyAnnoCollection->getPhpAnno(AnnoOneToOne::class);
			if (null !== ($annoOneToOne = $phpAnno->determineAnnotation())) {
				CastUtils::assertTrue($annoOneToOne instanceof AnnoOneToOne);
				$phpProperty->removePhpUse($annoOneToOne->getTargetEntityClass()->getName());
			}
		
			//@todo try to findout TargetClassName without Annotation
		
			$phpPropertyAnnoCollection->removePhpAnno(AnnoOneToOne::class);
			$phpProperty->removePhpUse(AnnoOneToOne::class);
		}
	}
}
