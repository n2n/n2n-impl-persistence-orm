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

use n2n\reflection\property\AccessProxy;
use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\operation\MergeOperation;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\spec\dbo\meta\data\QueryItem;
use n2n\persistence\Pdo;
use n2n\persistence\orm\EntityManager;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\store\ValueHash;
use n2n\persistence\orm\store\CommonValueHash;
use n2n\util\type\TypeConstraints;
use n2n\util\EnumUtils;
use n2n\impl\persistence\orm\property\compare\EnumColumnComparable;
use n2n\impl\persistence\orm\property\select\EnumSelection;

class EnumEntityProperty extends ColumnPropertyAdapter implements BasicEntityProperty {

	public function __construct(AccessProxy $accessProxy, $columnName, private \ReflectionEnum $enum) {
		parent::__construct(
				$accessProxy->createRestricted(TypeConstraints::namedType($enum, true),
						TypeConstraints::namedType($enum, $accessProxy->getSetterConstraint()->allowsNull())),
				$columnName);
	}
	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): EnumColumnComparable {
		return new EnumColumnComparable($this->createQueryColumn($metaTreePoint->getMeta()), $queryState);
	}

	public function createColumnComparableFromQueryItem(QueryItem $queryItem, QueryState $queryState): EnumColumnComparable {
		return new EnumColumnComparable($queryItem, $queryState);
	}

	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): EnumSelection {
		return new EnumSelection($this->createQueryColumn($metaTreePoint->getMeta()), $this->enum);
	}

	public function valueToRep($value): string {
		ArgUtils::valType($value, $this->enum);

		return $value->name;
	}

	public function repToValue(string $rep) {
		try {
			return $this->enum->getCase($rep)->getValue();
		} catch (\ReflectionException $e) {
			throw new \InvalidArgumentException(previous: $e);
		}
	}

	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash) {
		$rawValue = $this->buildRaw($value, $persistAction->getActionQueue()->getEntityManager()->getPdo());

		$persistAction->getMeta()->setRawValue($this->getEntityModel(), $this->getColumnName(), $rawValue);
	}

	public function createValueHash($value, EntityManager $em): ValueHash {
		if ($value === null) return new CommonValueHash(null);
		return new CommonValueHash($this->valueToRep($value));
	}

	public function mergeValue($value, $sameEntity, MergeOperation $mergeOperation) {
		if ($sameEntity || $value === null) {
			return $value;
		}

		ArgUtils::valType($value, $this->enum);
		return $value;
	}

	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $valueHash = null) {
	}

	public function parseValue($raw, Pdo $pdo) {
		return EnumUtils::backedToUnit($raw, $this->enum);
	}

	public function buildRaw($value, Pdo $pdo) {
		return EnumUtils::unitToBacked($value);
	}

	public function createSelectionFromQueryItem(QueryItem $queryItem, QueryState $queryState) {
		return new EnumSelection($queryItem, $this->enum);
	}
}
