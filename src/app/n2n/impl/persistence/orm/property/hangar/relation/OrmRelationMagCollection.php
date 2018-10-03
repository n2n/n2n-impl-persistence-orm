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

use n2n\web\dispatch\mag\MagCollection;
use n2n\web\dispatch\map\bind\BindingDefinition;
use hangar\util\EntityUtils;
use n2n\web\dispatch\map\bind\BindingErrors;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use n2n\impl\web\dispatch\mag\model\MultiSelectMag;
use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\FetchType;
use n2n\persistence\orm\annotation\OrmRelationAnnotation;
use n2n\persistence\orm\annotation\MappableOrmRelationAnnotation;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\util\StringUtils;

class OrmRelationMagCollection extends MagCollection {
	const PROP_NAME_TARGET_ENTITY_CLASS = 'targetEntityClass';
	const PROP_NAME_MAPPED_BY = 'mappedBy';
	const PROP_NAME_CASCADE_TYPE = 'cascadeType';
	const PROP_NAME_FETCH_TYPE = 'fetchType';
	const PROP_NAME_ORPHAN_REMOVAL = 'orphanRemoval';
	
	private $targetEntityClassOptions = [];
	private $groupedMappedByOptions = [];
	private $mappedByOptions = [];
	
	public function __construct(bool $addMappedBy = true, bool $addOrphanRemoval = false) {
		$this->targetEntityClassOptions = EntityUtils::getEntityClassNames();
		
		$this->addMag(self::PROP_NAME_TARGET_ENTITY_CLASS, new EnumMag('Target Entity',
				[null => null] + $this->targetEntityClassOptions, null, true,
				array('class' => 'hangar-orm-relation-target-entity'), 
				array('class' => 'hangar-orm-relation-target-entity-container')));
		
		if ($addMappedBy) {
			foreach ($this->targetEntityClassOptions as $entityClassName) {
				$entityClassDef = EntityUtils::createClassDef($entityClassName, true, false);
				foreach ($entityClassDef->getEntityModel()->getEntityProperties() as $entityProperty) {
					if (!$entityProperty->hasTargetEntityModel()) continue;
					
					$this->groupedMappedByOptions[$entityClassName][$entityProperty->getName()] = $entityProperty->getTargetEntityModel()->getClass()->getName();
					$this->mappedByOptions[$entityProperty->getName()] = $entityProperty->getName();
				}
			}
			
			$this->addMag(self::PROP_NAME_MAPPED_BY, 
					new EnumMag('Mapped By', $this->mappedByOptions, null, false,
							array('class' => 'hangar-orm-relation-mapped-by', 
									'data-grouped-options' => StringUtils::jsonEncode($this->groupedMappedByOptions)), 
							array('class' => 'hangar-orm-relation-mapped-by-container')));
		}
		
		$this->addMag(OrmRelationMagCollection::PROP_NAME_CASCADE_TYPE, 
				new MultiSelectMag('Cascade Type', self::getCascadeTypeOptions(), array(), 0, null, 
						array('class' => 'hangar-orm-relation-cascade-type'), 
						array('class' => 'hangar-orm-relation-cascade-type-container')));

		$this->addMag(OrmRelationMagCollection::PROP_NAME_FETCH_TYPE, 
				new EnumMag('Fetch Type', self::getFetchTypeOptions(), FetchType::LAZY, true));
		
		if ($addOrphanRemoval) {
			$this->addMag(OrmRelationMagCollection::PROP_NAME_ORPHAN_REMOVAL, 
					new BoolMag('Orphan removal', false));
		}
	}
	
	public function getTargetEntityClassOptions() {
		return $this->targetEntityClassOptions;
	}
	
	public function getGroupedMappedByOptions() {
		return $this->groupedMappedByOptions;
	}

	public function getMappedByOptions() {
		return $this->mappedByOptions;
	}
	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		parent::setupBindingDefinition($bd);
		
		if ($this->containsPropertyName(self::PROP_NAME_MAPPED_BY)) {
			$that = $this;
			$bd->closure(function($targetEntityClass, $mappedBy, BindingErrors $be) use ($that) {
				if (!$mappedBy) return;
				if (isset($that->groupedMappedByOptions[$targetEntityClass][$mappedBy])) return;
				//@todo check TargetEntityClassName
				
				$be->addError(self::PROP_NAME_MAPPED_BY, 'Invalid mapped by property name');
			});
		}
	}
	

	public function setTargetEntityClasName(string $targetEntityClassName) {
		$this->getMagByPropertyName(self::PROP_NAME_TARGET_ENTITY_CLASS)->setValue($targetEntityClassName);
	}
	
	public function setMappedBy(string $mappedBy) {
		$this->getMagByPropertyName(self::PROP_NAME_MAPPED_BY)->setValue($mappedBy);
	}
	
	public function setCascadeTypes(array $cascadeTypes) {
		$this->getMagByPropertyName(self::PROP_NAME_CASCADE_TYPE)->setValue($cascadeTypes);
	}
	
	public function setFetchType($fetchType) {
		$this->getMagByPropertyName(self::PROP_NAME_FETCH_TYPE)->setValue($fetchType);
	}
	
	public function setOrphanRemoval(bool $orphanRemoval) {
		$this->getMagByPropertyName(self::PROP_NAME_ORPHAN_REMOVAL)->setValue($orphanRemoval);
	}
	
	public static function buildCascadeTypeAnnoParam(array $cascadeTypes, $addTypeName = true) {
		$nameParts = array();
		if (in_array(CascadeType::PERSIST, $cascadeTypes)
				&& in_array(CascadeType::MERGE, $cascadeTypes)
				&& in_array(CascadeType::REFRESH, $cascadeTypes)
				&& in_array(CascadeType::REMOVE, $cascadeTypes)
				&& in_array(CascadeType::DETACH, $cascadeTypes)) {
			return '\\n2n\\persistence\\orm\\CascadeType::ALL';
		}
		
		foreach ($cascadeTypes as $cascadeType) {
			switch ((int) $cascadeType) {
				case CascadeType::PERSIST:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::PERSIST';
					break;
				case CascadeType::MERGE:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::MERGE';
					break;
				case CascadeType::REFRESH:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::REFRESH';
					break;
				case CascadeType::REMOVE:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::REMOVE';
					break;
				case CascadeType::DETACH:
					$nameParts[] = '\\n2n\\persistence\\orm\\CascadeType::DETACH';
					break;
				default:
					throw new IllegalStateException('Invalid cascade Type: ' . $cascadeType);
			}
		}
		
		if (empty($nameParts)) {
			return null;
		}
		
		return implode('|', $nameParts);
	}
	
	public static function buildFetchTypeAnnoParam($fetchType, $addTypeName = true) {
		switch ($fetchType) {
			case FetchType::LAZY:
				return null;
			case FetchType::EAGER:
				return '\n2n\persistence\orm\FetchType::EAGER';
			default:
				throw new IllegalStateException('Invalid fetch Type: ' . $fetchType);
		}
	}
	
	public static function getCascadeTypeOptions() {
		return array(CascadeType::PERSIST => 'PERSIST',
				CascadeType::MERGE => 'MERGE', CascadeType::REMOVE => 'REMOVE', CascadeType::REFRESH => 'REFRESH',
				CascadeType::DETACH => 'DETACH');
	}
	
	public static function getFetchTypeOptions() {
		return array(FetchType::EAGER => 'Eager', FetchType::LAZY => 'Lazy');
	}
	
	public function setValuesByAnnotation(OrmRelationAnnotation $annotation) {
		$this->setCascadeTypes(self::buildCascadeTypes($annotation->getCascadeType()));
		$this->setFetchType($annotation->getFetchType());
		$this->setTargetEntityClasName($annotation->getTargetEntityClass()->getName());
		
		if ($annotation instanceof MappableOrmRelationAnnotation && null !== $annotation->getMappedBy()) {
			$this->setMappedBy($annotation->getMappedBy());
		}
		
		if ($annotation instanceof AnnoOneToMany || $annotation instanceof AnnoOneToOne) {
			$this->setOrphanRemoval($annotation->isOrphanRemoval());
		}
	}
	
	private static function buildCascadeTypes($cascadeType) {
		$cascadeTypes = array();
		if ($cascadeType & CascadeType::DETACH) {
			$cascadeTypes[CascadeType::DETACH] = CascadeType::DETACH;
		}
		
		if ($cascadeType & CascadeType::MERGE) {
			$cascadeTypes[CascadeType::MERGE] = CascadeType::MERGE;
		}
		
		if ($cascadeType & CascadeType::PERSIST) {
			$cascadeTypes[CascadeType::PERSIST] = CascadeType::PERSIST;
		}
		
		if ($cascadeType & CascadeType::REFRESH) {
			$cascadeTypes[CascadeType::REFRESH] = CascadeType::REFRESH;
		}
		
		if ($cascadeType & CascadeType::REMOVE) {
			$cascadeTypes[CascadeType::REMOVE] = CascadeType::REMOVE;
		}
		return $cascadeTypes;
	}
	
	
}