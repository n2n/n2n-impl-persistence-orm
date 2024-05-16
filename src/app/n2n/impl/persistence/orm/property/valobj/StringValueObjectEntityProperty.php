<?php

namespace n2n\impl\persistence\orm\property\valobj;

use n2n\impl\persistence\orm\property\ColumnPropertyAdapter;
use n2n\persistence\orm\store\operation\MergeOperation;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\ValueHash;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\reflection\property\AccessProxy;
use n2n\util\type\TypeConstraints;
use n2n\persistence\orm\criteria\compare\ScalarColumnComparable;

class StringValueObjectEntityProperty extends ColumnPropertyAdapter {

	public function __construct(AccessProxy $accessProxy, $columnName, \ReflectionClass $class) {
		parent::__construct(
				$accessProxy->createRestricted(TypeConstraints::namedType($class, true, true),
						TypeConstraints::namedType($class, $accessProxy->getSetterConstraint()->allowsNull())),
				$columnName);
	}

	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): ScalarColumnComparable {
		return new ScalarColumnComparable($this->createQueryColumn($metaTreePoint->getMeta()), $queryState, 'bool');
	}

	public function mergeValue($value, $sameEntity, MergeOperation $mergeOperation) {

	}

	public function supplyPersistAction(PersistAction $persistingJob, $value, ValueHash $valueHash, ?ValueHash $oldValueHash) {
		// TODO: Implement supplyPersistAction() method.
	}

	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash) {
		// TODO: Implement supplyRemoveAction() method.
	}

	public function createValueHash($value, EntityManager $em): ValueHash {
		// TODO: Implement createValueHash() method.
	}
}