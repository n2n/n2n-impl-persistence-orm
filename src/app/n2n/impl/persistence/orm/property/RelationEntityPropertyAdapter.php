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

use n2n\impl\persistence\orm\property\relation\Relation;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\property\AccessProxy;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\MasterRelation;
use n2n\impl\persistence\orm\property\relation\MappedRelation;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\persistence\orm\query\from\meta\TreePointMeta;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\store\ValueHash;
use n2n\persistence\orm\criteria\JoinType;
use n2n\impl\persistence\orm\property\relation\LazyRelation;
use n2n\persistence\orm\query\select\Selection;
use n2n\util\magic\MagicContext;
use n2n\persistence\orm\query\from\TreePoint;

abstract class RelationEntityPropertyAdapter extends EntityPropertyAdapter implements RelationEntityProperty {
	protected $master;
	protected $type;
	private LazyRelation $lazyRelation;
	private ?Relation $relation = null;
	
	public function __construct(AccessProxy $accessProxy, bool $master, string $type) {
		parent::__construct($accessProxy);
		$this->master = $master;
		$this->type = $type;
	} 

	public function getType(): string {
		return $this->type;
	}
	
	public function isMaster(): bool {
		return $this->master;
	}
	
	public function isToMany(): bool {
		return $this->type == self::TYPE_ONE_TO_MANY || $this->type == self::TYPE_MANY_TO_MANY;
	}

	function isFromMany(): bool {
		return $this->type == self::TYPE_MANY_TO_ONE || $this->type == self::TYPE_MANY_TO_MANY;
	}

	public function copy($value) {
		return $value;
	}

	public function getLazyRelation(): LazyRelation {
		if (!isset($this->lazyRelation)) {
			throw new IllegalStateException('No relation assigned for ' . $this->__toString());
		}

		return $this->lazyRelation;
	}


	public function getRelation(): Relation {
		if ($this->relation !== null) {
			return $this->relation;
		}

		$relation = $this->getLazyRelation()->obtainRelation();

		if ($this->master) {
			ArgUtils::assertTrue($relation instanceof MasterRelation);
		} else {
			ArgUtils::assertTrue($relation instanceof MappedRelation);
		}

		$this->relation = $relation;
		unset($this->lazyRelation);

		return $this->relation;
	}
	
	protected function assignLazyRelation(LazyRelation $lazyRelation) {
		$this->lazyRelation = $lazyRelation;
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityProperty::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState): Selection {
		return $this->getRelation()->createSelection($metaTreePoint, $queryState);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\JoinableEntityProperty::createJoinTreePoint()
	 */
	public function createJoinTreePoint(TreePointMeta $treePointMeta, QueryState $queryState) {
		return $this->getRelation()->createJoinTreePoint($treePointMeta, $queryState);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\CascadableEntityProperty::prepareSupplyJob()
	 */
	public function prepareSupplyJob(SupplyJob $supplyJob, mixed $value, ?ValueHash $valueHash, ?ValueHash $oldValueHash): void {
		$this->getRelation()->prepareSupplyJob($supplyJob, $value, $valueHash, $oldValueHash);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::supplyPersistAction()
	 */
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash): void {
		$this->getRelation()->supplyPersistAction($persistAction, $value, $valueHash, $oldValueHash);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::supplyRemoveAction()
	 */
	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash) {
		$this->getRelation()->supplyRemoveAction($removeAction, $value, $oldValueHash);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::createValueHash()
	 */
	public function createValueHash(mixed $value, MagicContext $magicContext): ValueHash {
		return $this->getRelation()->createValueHash($value, $magicContext);
	}
	
	public function hasTargetEntityModel(): bool {
		return true;
	}
	
	public function getTargetEntityModel(): EntityModel {
		return $this->relation?->getTargetEntityModel() ?? $this->getLazyRelation()->getTargetEntityModel();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\JoinableEntityProperty::getAvailableJoinTypes()
	 */
	public function getAvailableJoinTypes(TreePoint $treePoint): array {
		return JoinType::getValues();
	}

	function ensureInit(): void {
		$this->getRelation();
	}
}
