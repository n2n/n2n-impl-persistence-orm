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

use n2n\impl\persistence\orm\property\relation\Relation;
use n2n\util\type\TypeConstraint;
use n2n\persistence\orm\store\operation\CascadeOperation;
use n2n\util\col\ArrayUtils;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\selection\ArrayObjectProxy;
use n2n\persistence\orm\store\operation\MergeOperation;
use n2n\persistence\orm\CascadeType;
use n2n\impl\persistence\orm\property\relation\ToManyRelation;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\property\CustomComparableEntityProperty;
use n2n\util\type\TypeConstraints;
use n2n\impl\persistence\orm\property\relation\LazyRelation;
use n2n\persistence\orm\criteria\compare\CustomComparable;

class ToManyEntityProperty extends RelationEntityPropertyAdapter implements CustomComparableEntityProperty {
	public function setLazyRelation(LazyRelation $lazyRelation) {
		parent::assignLazyRelation($lazyRelation);
//		ArgUtils::assertTrue($this->relation instanceof ToManyRelation);
		$this->accessProxy = $this->accessProxy->createRestricted(TypeConstraint::createArrayLike(null, true,
				TypeConstraint::createSimple($lazyRelation->getTargetEntityModel()->getClass()->getName()),
				array('n2n\impl\persistence\orm\property\relation\selection\ArrayObjectProxy')));
	}
	
	public function cascade(mixed $value, int $cascadeType, CascadeOperation $cascadeOperation): void {
		if ($value === null || !($this->getRelation()->getCascadeType() & $cascadeType)) return;
		
		if ($cascadeType !== CascadeType::REMOVE && $value instanceof ArrayObjectProxy && !$value->isInitialized()) {
			return;
		}
		
		ArgUtils::assertTrue(ArrayUtils::isArrayLike($value));
		foreach ($value as $entity) {
			$cascadeOperation->cascade($entity);
		}
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityProperty::mergeValue()
	 */
	public function mergeValue(mixed $value, bool $sameEntity, MergeOperation $mergeOperation): mixed {
		if ($value === null) return null;
	
		if ($value instanceof ArrayObjectProxy && !$value->isInitialized()) return $value;
		
		$mergedEntities = new \ArrayObject();
		if ($this->getRelation()->getCascadeType() & CascadeType::MERGE) {
			foreach ($value as $key => $targetEntity) {
				$mergedEntities[$key] = $mergeOperation->mergeEntity($targetEntity);
			}
		}
		return $mergedEntities;
	}

	public function createCustomComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): CustomComparable {
		return $this->getRelation()->createCustomComparable($metaTreePoint, $queryState);
	}
	
// 	public function readValue($object) {
// 		$value = parent::readValue($object);
// 		if ($value === null && $this->isToMany()) {
// 			return new \ArrayObject();
// 		}
// 		return $value;
// 	}
}
