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
use n2n\util\type\TypeConstraint;
use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\criteria\compare\ScalarColumnComparable;
use n2n\persistence\orm\query\select\SimpleSelection;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\operation\MergeOperation;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\spec\dbo\meta\data\QueryItem;
use n2n\persistence\Pdo;
use n2n\persistence\orm\EntityManager;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\store\ValueHash;
use n2n\persistence\orm\store\CommonValueHash;
use n2n\util\type\TypeName;
use n2n\util\type\TypeConstraints;
use n2n\persistence\orm\criteria\compare\ColumnComparable;
use n2n\persistence\orm\query\select\Selection;
use n2n\util\magic\MagicContext;

class StringEntityProperty extends ColumnPropertyAdapter implements BasicEntityProperty {
	/**
	 * @param AccessProxy $accessProxy
	 * @param string $columnName
	 */
	public function __construct(AccessProxy $accessProxy, $columnName) {
		parent::__construct(
				$accessProxy->createRestricted(TypeConstraints::string(true, true),
						TypeConstraints::string($accessProxy->getSetterConstraint()->allowsNull())),
				$columnName);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\ColumnComparableEntityProperty::createComparisonStrategy()
	 */
	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): ColumnComparable {
		return new ScalarColumnComparable($this->createQueryColumn($metaTreePoint->getMeta()), $queryState);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\BasicEntityProperty::createColumnComparableFromQueryItem()
	 */
	public function createColumnComparableFromQueryItem(QueryItem $queryItem, QueryState $queryState): ColumnComparable {
		return new ScalarColumnComparable($queryItem, $queryState);
	}

	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): Selection {
		return new SimpleSelection($this->createQueryColumn($metaTreePoint->getMeta()), TypeName::STRING);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\BasicEntityProperty::valueToRep()
	 */
	public function valueToRep(mixed $value): string {
		ArgUtils::valScalar($value);
		return (string) $value;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\BasicEntityProperty::repToValue()
	 */
	public function repToValue(string $rep): mixed {
		return $rep;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::supplyPersistAction()
	 */
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash): void {
// 		$pdoDataType = null;
// 		if (is_bool($mappedValue)) {
// 			$pdoDataType = PDO::PARAM_BOOL;
// 		}
		$persistAction->getMeta()->setRawValue($this->getEntityModel(), $this->getColumnName(), $value, /*$pdoDataType*/null, $this);
	}


	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::createValueHash()
	 */
	public function createValueHash(mixed $value, MagicContext $magicContext): ValueHash {
		return new CommonValueHash($value);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::mergeValue()
	 */
	public function mergeValue(mixed $value, bool $sameEntity, MergeOperation $mergeOperation): mixed {
		return $value;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::supplyRemoveAction()
	 */
	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash) {
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\BasicEntityProperty::parseValue()
	 */
	public function parseValue(mixed $raw, Pdo $pdo): mixed {
		ArgUtils::valScalar($raw);
		if ($raw === null) {
			return null;
		}
		return (string) $raw;
	}

	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\BasicEntityProperty::buildRaw()
	 */
	public function buildRaw(mixed $value, Pdo $pdo): mixed {
		return $value;
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\BasicEntityProperty::createSelectionFromQueryItem()
	 */
	public function createSelectionFromQueryItem(QueryItem $queryItem, QueryState $queryState): Selection {
		return new SimpleSelection($queryItem, TypeName::STRING);
	}



// 	public function getTypeConstraint() {
//  		return $this->getAccessProxy()->getConstraint();
// 	}

// 	public function buildComparableValue($value) {
// 		return $value;
// 	}

// 	public static function areConstraintsTypical(?TypeConstraint $constraints = null) {
// 		return is_null($constraints)
// 				|| (is_null($constraints->getParamClass()) && !$constraints->isArray());
// 	}
}
