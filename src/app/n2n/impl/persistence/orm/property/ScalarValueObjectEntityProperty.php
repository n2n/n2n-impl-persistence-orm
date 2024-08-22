<?php

namespace n2n\impl\persistence\orm\property;

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
use n2n\persistence\orm\criteria\compare\PlaceholderValueMapper;
use n2n\spec\valobj\scalar\StringValueObject;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\criteria\compare\CallbackColumnComparable;
use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\spec\dbo\meta\data\QueryItem;
use n2n\persistence\orm\criteria\compare\ColumnComparable;
use n2n\persistence\orm\query\select\Selection;
use n2n\persistence\Pdo;
use n2n\persistence\orm\query\select\EagerValueSelection;
use n2n\persistence\orm\query\select\EagerValueMapper;
use n2n\persistence\orm\store\CommonValueHash;
use n2n\util\ex\ExUtils;
use n2n\util\type\TypeConstraint;
use n2n\spec\valobj\scalar\ScalarValueObject;
use n2n\spec\valobj\err\IllegalValueException;
use n2n\persistence\orm\CorruptedDataException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\util\type\UnionTypeConstraint;

class ScalarValueObjectEntityProperty extends ColumnPropertyAdapter implements BasicEntityProperty {

	public function __construct(AccessProxy $accessProxy, string $columnName,
			private TypeConstraint $scalarTypeConstraint, private \ReflectionClass $class) {
		parent::__construct(
				$accessProxy->createRestricted(TypeConstraints::namedType($class, true, true),
						TypeConstraints::namedType($class, $accessProxy->getSetterConstraint()->allowsNull())),
				$columnName);
	}

	function getScalarTypeConstraint(): TypeConstraint {
		return $this->scalarTypeConstraint;
	}

	function getClass(): \ReflectionClass {
		return $this->class;
	}

	public function createColumnComparableFromQueryItem(QueryItem $queryItem, QueryState $queryState): ColumnComparable {
		$callback = new class() implements PlaceholderValueMapper {
			function __invoke(mixed $value): float|int|string|bool|null {
				if ($value === null || is_scalar($value)) {
					return $value;
				}

				ArgUtils::assertTrue(assert($value instanceof ScalarValueObject));
				return $value->toScalar();
			}
		};

		return new CallbackColumnComparable($queryItem, $queryState,
				TypeConstraints::type([/*$this->scalarTypeConstraint*/ 'scalar', $this->class->getName()]), $callback);
	}

	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): ColumnComparable {
		return $this->createColumnComparableFromQueryItem($this->createQueryColumn($metaTreePoint->getMeta()), $queryState);
	}

	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): Selection {
		return $this->createSelectionFromQueryItem($this->createQueryColumn($metaTreePoint->getMeta()), $queryState);
	}

	public function createSelectionFromQueryItem(QueryItem $queryItem, QueryState $queryState): Selection {
		$mapper = new class($this->class, $this->scalarTypeConstraint) implements EagerValueMapper {
			function __construct(private \ReflectionClass $class, private TypeConstraint $scalarTypeConstraint) {
			}

			function __invoke(mixed $value): mixed {
				if ($value === null) {
					return null;
				}

				try {
					return $this->class->newInstance($this->scalarTypeConstraint->validate($value));
				} catch (IllegalValueException|ValueIncompatibleWithConstraintsException $e) {
					throw new CorruptedDataException(previous: $e);
				}
			}
		};

		return new EagerValueSelection($queryItem, $this->scalarTypeConstraint, $mapper);
	}

	public function mergeValue(mixed $value, bool $sameEntity, MergeOperation $mergeOperation): mixed {
		return $value;
	}

	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash): void {
		assert($value === null || $value instanceof ScalarValueObject);
		$persistAction->getMeta()->setRawValue($this->getEntityModel(), $this->getColumnName(), $value?->toScalar(), null, $this);
	}

	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash) {
	}

	public function createValueHash(mixed $value, EntityManager $em): ValueHash {
		assert($value === null || $value instanceof ScalarValueObject);
		return new CommonValueHash($value?->toScalar());
	}

	public function valueToRep(mixed $value): string {
		assert($value instanceof StringValueObject);
		return $value->toScalar();
	}

	public function repToValue(string $rep): mixed {
		return ExUtils::try(fn () => $this->class->newInstance($rep));
	}

	public function parseValue(mixed $raw, Pdo $pdo): mixed {
		$value =  $this->scalarTypeConstraint->validate($raw);
		if ($value === null) {
			return null;
		}

		return ExUtils::try(fn () => $this->class->newInstance($value));
	}

	public function buildRaw(mixed $value, Pdo $pdo): mixed {
		ArgUtils::assertTrue($value === null || $value instanceof StringValueObject);
		return $value?->toScalar();
	}
}