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

use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\criteria\compare\ComparisonStrategy;
use n2n\persistence\orm\FetchType;
use n2n\impl\persistence\orm\property\relation\selection\ToOneRelationSelection;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHasher;
use n2n\impl\persistence\orm\property\relation\compare\IdColumnComparableDecorator;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\impl\persistence\orm\property\relation\util\ToOneUtils;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHash;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\criteria\compare\ColumnComparable;
use n2n\persistence\orm\query\select\Selection;
use n2n\util\magic\MagicContext;
use n2n\persistence\orm\OrmUtils;

class JoinTableToOneRelation extends JoinTableRelation implements ToOneRelation {
	private $toOneUtils;
	
	public function __construct(EntityProperty $entityProperty, EntityModel $targetEntityModel) {
		parent::__construct($entityProperty, $targetEntityModel);
		$this->toOneUtils = new ToOneUtils($this, true);
	}

	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\ToOneRelation::createRepresentingQueryItem()
	 */
	public function createRepresentingQueryItem(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		return $metaTreePoint->requestPropertyRepresentableQueryItem($this->createTargetIdTreePath());
	}

	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState): ColumnComparable {
		$columnComparable = $metaTreePoint->requestPropertyComparisonStrategy($this->createTargetIdTreePath())
				->getColumnComparable();

		return new IdColumnComparableDecorator($columnComparable,
				$this->targetEntityModel);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): Selection {
		$idSelection = $metaTreePoint->requestPropertySelection($this->createTargetIdTreePath());
	
		$toOneRelationSelection = new ToOneRelationSelection($this->targetEntityModel, $idSelection, $queryState);
		$toOneRelationSelection->setLazy($this->fetchType == FetchType::LAZY);
		return $toOneRelationSelection;
	}
	
	public function prepareSupplyJob(SupplyJob $supplyJob, mixed $value, ?ValueHash $valueHash, ?ValueHash $oldValueHash): void {
		$this->toOneUtils->prepareSupplyJob($supplyJob, $value, $valueHash, $oldValueHash);
	}
	
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash): void {
		ArgUtils::assertTrue($oldValueHash === null || $oldValueHash instanceof ToOneValueHash);

		if ($value === null) {
			if ($oldValueHash === null || $oldValueHash->getIdRep() === null) return;

			$this->createJoinTableActionFromPersistAction($persistAction);
			return;
		}

		$persistAction->getMeta()->markChange($this->entityProperty);

		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
		$actionQueue = $persistAction->getActionQueue();

		$targetId = OrmUtils::extractId($value);
		$targetPersistAction = null;

		if ($targetId === null) {
			$targetPersistAction = $actionQueue->getPersistAction($value);
			if ($targetPersistAction->hasId()) {
				$targetId = $targetPersistAction->getId();
			}
		}
		
		if ($targetId !== null) {
			$targetIdRep = $targetIdProperty->valueToRep($targetId);
			if ($oldValueHash !== null && $targetIdRep === $oldValueHash->getIdRep()) return;

			$joinTableAction = $this->createJoinTableActionFromPersistAction($persistAction);
			$joinTableAction->addInverseJoinIdRaw($targetIdProperty->buildRaw($targetId, $joinTableAction->getPdo()));
			return;
		}		
	
		$joinTableAction = $this->createJoinTableActionFromPersistAction($persistAction);
		$joinTableAction->addDependent($targetPersistAction);
		assert($valueHash instanceof ToOneValueHash);
		$targetPersistAction->executeAtEnd(function () use ($joinTableAction, $targetPersistAction, $targetIdProperty, $valueHash) {
			$joinTableAction->addInverseJoinIdRaw($targetIdProperty->buildRaw($targetPersistAction->getId(), $joinTableAction->getPdo()));
			
			$hasher = new ToOneValueHasher($this->getTargetIdEntityProperty());
			$hasher->reportId($targetPersistAction->getId(), $valueHash);
		});
	}
	
	public function createValueHash(mixed $value, MagicContext $magicContext): ValueHash {
		return ToOneValueHasher::createFromEntityModel($this->targetEntityModel)
				->createValueHash($value);
	}
}
