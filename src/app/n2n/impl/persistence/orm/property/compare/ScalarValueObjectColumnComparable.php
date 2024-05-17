<?php

namespace n2n\impl\persistence\orm\property\compare;

use n2n\persistence\orm\criteria\compare\ColumnComparableAdapter;
use n2n\spec\dbo\meta\data\QueryItem;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\util\uri\Url;
use n2n\util\type\ArgUtils;
use n2n\spec\dbo\meta\data\impl\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryPartGroup;
use n2n\util\ex\NotYetImplementedException;
use n2n\util\type\TypeConstraints;
use n2n\spec\valobj\scalar\ScalarValueObject;

class ScalarValueObjectColumnComparable extends ColumnComparableAdapter {


	public function __construct(QueryItem $comparableQueryItem, private QueryState $queryState) {
		parent::__construct(CriteriaComparator::getOperators(false),
				TypeConstraints::type([ScalarValueObject::class, 'scalar'], true), $comparableQueryItem);

	}

	public function buildCounterpartQueryItemFromValue(string $operator, mixed $value): QueryItem {
		if ($operator != CriteriaComparator::OPERATOR_IN  && $operator != CriteriaComparator::OPERATOR_NOT_IN) {
			ArgUtils::valType($value, Url::class, true);
			return new QueryPlaceMarker($this->queryState->registerPlaceholderValue((string) $value->toScalar()));
		}

		ArgUtils::valArray($value, Url::class);

		$queryPartGroup = new QueryPartGroup();
		foreach ($value as $fieldValue) {
			$queryPartGroup->addQueryPart(
					new QueryPlaceMarker($this->queryState->registerPlaceholderValue((string) $fieldValue)));
		}
		return $queryPartGroup;
	}

	public function buildCounterpartPlaceholder($operator, $value) {
		throw new NotYetImplementedException();
	}
}