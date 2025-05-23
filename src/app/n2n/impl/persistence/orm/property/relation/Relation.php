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
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\persistence\orm\query\from\meta\TreePointMeta;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\magic\MagicContext;

interface Relation {
	
	public function getEntityProperty();
	
	/**
	 * @return \n2n\persistence\orm\model\EntityModel 
	 */
	public function getTargetEntityModel();
	
	/**
	 * @return \n2n\impl\persistence\orm\property\relation\util\ActionMarker
	 */
	public function getActionMarker();
	
	/**
	 * @return string 
	 */
	public function getFetchType();
	
	/**
	 * @return int 
	 */
	public function getCascadeType();
	
	/**
	 * @return boolean 
	 */
	public function isOrphanRemoval();
	
	/**
	 * @param MetaTreePoint $metaTreePoint
	 * @param QueryState $queryState
	 * @return \n2n\impl\persistence\orm\property\relation\tree\JoinColumnTreePoint
	 */
	public function createJoinTreePoint(TreePointMeta $treePointMeta, QueryState $queryState);
	
	/**
	 * @param MetaTreePoint $metaTreePoint
	 * @param QueryState $queryState
	 * @return \n2n\persistence\orm\query\select\Selection
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState);
	
	/**
	 * @param mixed $value
	 * @param mixed $oldValueHash
	 * @param SupplyJob $supplyJob
	 */
	public function prepareSupplyJob(SupplyJob $supplyJob, mixed $value, ?ValueHash $valueHash, ?ValueHash $oldValueHash): void;
	
	/**
	 * @param mixed $value
	 * @param mixed $oldValueHash
	 * @param PersistAction $persistAction
	 */
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash);
	
	/**
	 * @param mixed $value
	 * @param mixed $oldValueHash
	 * @param RemoveAction $removeAction
	 */
	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash);
	
	public function createValueHash(mixed $value, MagicContext $magicContext): ValueHash;
}
