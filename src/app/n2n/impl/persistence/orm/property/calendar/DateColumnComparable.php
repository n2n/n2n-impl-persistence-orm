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
namespace n2n\impl\persistence\orm\property\calendar;

use n2n\util\type\ArgUtils;
use n2n\spec\dbo\meta\data\QueryItem;
use n2n\util\type\TypeConstraint;
use n2n\persistence\orm\query\QueryState;
use n2n\spec\dbo\meta\data\impl\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryPartGroup;
use n2n\persistence\orm\criteria\compare\ColumnComparableAdapter;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\util\calendar\Date;

class DateColumnComparable extends ColumnComparableAdapter {

	public function __construct(QueryItem $comparableQueryItem, private QueryState $queryState) {
		parent::__construct(CriteriaComparator::getOperators(false), 
				TypeConstraint::createSimple(Date::class, true), $comparableQueryItem);
	}
	
	private function buildDateTimeRawValue(?Date $value): ?string {
		if ($value === null) {
			return null;
		}

		return (string) $value;
	}
	
	public function buildCounterpartQueryItemFromValue(string $operator, mixed $value): QueryItem {
		if ($operator != CriteriaComparator::OPERATOR_IN && $operator != CriteriaComparator::OPERATOR_NOT_IN) {
			ArgUtils::valType($value, Date::class, true);
			return new QueryPlaceMarker($this->queryState->registerPlaceholderValue(
					$this->buildDateTimeRawValue($value)));
		} 
		
		ArgUtils::valArray($value, Date::class);
		
		$queryPartGroup = new QueryPartGroup();
		foreach ($value as $fieldValue) {
			$queryPartGroup->addQueryPart(
					new QueryPlaceMarker($this->queryState->registerPlaceholderValue(
							$this->buildDateTimeRawValue($fieldValue))));
		}
		return $queryPartGroup;
	}
	
	public function buildCounterpartPlaceholder($operator, $value) {
		
	}
	
// 	public function parseComparableValue($operator, $value) {
// 		
		
// 		return $value;
// 	}
	
}
