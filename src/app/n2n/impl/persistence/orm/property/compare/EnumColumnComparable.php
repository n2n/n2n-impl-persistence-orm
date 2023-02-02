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
namespace n2n\impl\persistence\orm\property\compare;

use n2n\util\type\ArgUtils;
use n2n\persistence\meta\data\QueryItem;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\meta\data\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryPartGroup;
use n2n\util\ex\NotYetImplementedException;
use n2n\persistence\orm\criteria\compare\ColumnComparableAdapter;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\util\type\TypeConstraints;
use n2n\util\EnumUtils;

class EnumColumnComparable extends ColumnComparableAdapter {

	public function __construct(QueryItem $comparableQueryItem, private QueryState $queryState) {
		parent::__construct(CriteriaComparator::getOperators(false), 
				TypeConstraints::namedType(\UnitEnum::class, true), $comparableQueryItem);
	}
	
	private function buildEnumRawValue(?\UnitEnum $value) {
		return EnumUtils::unitToBacked($value);
	}
	
	public function buildCounterpartQueryItemFromValue($operator, $value) {
		if ($operator != CriteriaComparator::OPERATOR_IN  && $operator != CriteriaComparator::OPERATOR_NOT_IN) {
			ArgUtils::valType($value, \UnitEnum::class, true);
			return new QueryPlaceMarker($this->queryState->registerPlaceholderValue(
					$this->buildEnumRawValue($value)));
		} 
		
		ArgUtils::valArray($value, \UnitEnum::class);
		
		$queryPartGroup = new QueryPartGroup();
		foreach ($value as $fieldValue) {
			$queryPartGroup->addQueryPart(
					new QueryPlaceMarker($this->queryState->registerPlaceholderValue(
							$this->buildEnumRawValue($fieldValue))));
		}
		return $queryPartGroup;
	}
	
	public function buildCounterpartPlaceholder($operator, $value) {
		throw new NotYetImplementedException();
	}
	
}
