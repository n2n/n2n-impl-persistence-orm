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
namespace n2n\impl\persistence\orm\property\relation;

use n2n\persistence\orm\criteria\compare\ComparisonStrategy;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\FetchType;
use n2n\impl\persistence\orm\property\relation\selection\ToOneRelationSelection;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHasher;
use n2n\impl\persistence\orm\property\relation\compare\IdColumnComparableDecorator;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\impl\persistence\orm\property\relation\util\ToOneUtils;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\query\from\TreePath;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\PersistenceOperationException;
use n2n\persistence\orm\store\EntityInfo;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHash;
use n2n\persistence\orm\criteria\compare\ColumnComparable;
use n2n\persistence\orm\query\select\Selection;
use n2n\util\magic\MagicContext;

class PropertyMappedOneToOneRelation extends MappedRelation implements ToOneRelation  {
	private $toOneUtils;

	public function __construct(EntityProperty $entityProperty, EntityModel $targetEntityModel,
			EntityProperty $targetEntityProperty) {
		parent::__construct($entityProperty, $targetEntityModel, $targetEntityProperty);
		$this->toOneUtils = new ToOneUtils($this, false);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\ToOneRelation::createRepresentingQueryItem()
	 */
	public function createRepresentingQueryItem(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		return $metaTreePoint->requestPropertyRepresentableQueryItem($this->createTargetIdTreePath());
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\ToOneRelation::createColumnComparable()
	 */
	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): ColumnComparable {
		$comparisonStrategy = $metaTreePoint->requestPropertyComparisonStrategy($this->createTargetIdTreePath());

		IllegalStateException::assertTrue($comparisonStrategy->getType() == ComparisonStrategy::TYPE_COLUMN);

		return new IdColumnComparableDecorator($comparisonStrategy->getColumnComparable(), $this->targetEntityModel);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): Selection {
		$targetTreePath = $this->createTargetIdTreePath();

		$idSelection = $metaTreePoint->requestCustomPropertyJoinTreePoint($this->entityProperty, false)
			->requestPropertySelection(new TreePath(array($this->targetEntityModel->getIdDef()
				->getPropertyName())));

		$entitySelection = new ToOneRelationSelection($this->targetEntityModel, $idSelection, $queryState);
		$entitySelection->setLazy($this->fetchType == FetchType::LAZY);
		return $entitySelection;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createValueHash()
	 */
	public function createValueHash(mixed $value, MagicContext $magicContext): ValueHash {
		return ToOneValueHasher::createFromEntityModel($this->targetEntityModel)
			->createValueHash($value);
	}
	/**
	 * @param mixed $value
	 * @param ValueHash $oldValueHash
	 * @param SupplyJob $supplyJob
	 */
	public function prepareSupplyJob(SupplyJob $supplyJob, mixed $value, ?ValueHash $valueHash, ?ValueHash $oldValueHash): void {
		$this->toOneUtils->prepareSupplyJob($supplyJob, $value, $valueHash, $oldValueHash);
	}

	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash,
			?ValueHash $oldValueHash): void {
		if ($value === null) {
			return;
		}

		try {
			$targetPersistAction = $persistAction->getActionQueue()->getPersistAction($value);
		} catch (PersistenceOperationException $e) {
			throw new PersistenceOperationException($this->entityProperty->toPropertyString() . ' of entity obj '
					. EntityInfo::buildEntityString($persistAction->getEntityModel(), $persistAction->getId())
					. ' contains value that can not be persisted.', 0, $e, 3);
		}

		if ($targetPersistAction->hasId()) {
			return;
		}

		ArgUtils::assertTrue($valueHash instanceof ToOneValueHash);
		$targetPersistAction->executeAtEnd(function () use ($valueHash, $targetPersistAction) {
			$targetId = $targetPersistAction->getId();

			$hasher = new ToOneValueHasher($this->getTargetIdEntityProperty());
			$hasher->reportId($targetId, $valueHash);
		});
	}
}
