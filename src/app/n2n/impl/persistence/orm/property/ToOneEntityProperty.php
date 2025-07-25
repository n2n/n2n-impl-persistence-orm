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

use n2n\persistence\orm\property\ColumnComparableEntityProperty;
use n2n\persistence\orm\property\QueryItemRepresentableEntityProperty;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\store\operation\MergeOperation;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\store\operation\CascadeOperation;
use n2n\util\type\TypeConstraints;
use n2n\impl\persistence\orm\property\relation\LazyRelation;
use n2n\persistence\orm\criteria\compare\ColumnComparable;
use n2n\persistence\orm\criteria\JoinType;
use n2n\persistence\orm\query\from\TreePoint;

class ToOneEntityProperty extends RelationEntityPropertyAdapter implements ColumnComparableEntityProperty, 
		QueryItemRepresentableEntityProperty {
	
	public function setLazyRelation(LazyRelation $lazyRelation) {
		parent::assignLazyRelation($lazyRelation);

		$this->accessProxy = $this->accessProxy->createRestricted(TypeConstraints::namedType(
				$lazyRelation->getTargetEntityModel()->getClass()->getName(), true));
	}
	
	public function createRepresentingQueryItem(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		return $this->getRelation()->createRepresentingQueryItem($metaTreePoint, $queryState);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\ColumnComparableEntityProperty::createColumnComparable()
	*/
	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): ColumnComparable {
		return $this->getRelation()->createColumnComparable($metaTreePoint, $queryState);
	}
	
	public function cascade(mixed $value, int $cascadeType, CascadeOperation $cascadeOperation): void {
		if ($value === null) return;
		
		if ($this->getRelation()->getCascadeType() & $cascadeType) {
			$cascadeOperation->cascade($value);
		}
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityProperty::mergeValue()
	*/
	public function mergeValue(mixed $value, bool $sameEntity, MergeOperation $mergeOperation): mixed {
		if ($value === null) return null;
				
		if ($this->getRelation()->getCascadeType() & CascadeType::MERGE) {
			return $mergeOperation->mergeEntity($value);
		}
		
		return $value;
	}

	public function getAvailableJoinTypes(TreePoint $treePoint): array {
		if ($this->accessProxy->getSetterConstraint()->allowsNull()
				|| $treePoint->getJoinType() === JoinType::LEFT
				|| $treePoint->getJoinType() === JoinType::CROSS) {
			return parent::getAvailableJoinTypes($treePoint);
		}

		if ($this->isFromMany()) {
			return [JoinType::INNER, JoinType::RIGHT];
		}

		return [JoinType::INNER];
	}
	
//	public function hasEmbeddedEntityPropertyCollection(): bool {
//		return true;
//	}
//
//	public function getEmbeddedEntityPropertyCollection(): EntityPropertyCollection {
//		return $this->getRelation()->getTargetEntityModel();
//	}
}
