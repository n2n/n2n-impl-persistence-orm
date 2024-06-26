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
namespace n2n\impl\persistence\orm\property\relation\tree;

use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\SelectStatementBuilder;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\orm\query\from\JoinedTreePointAdapter;

class JoinColumnTreePoint extends JoinedTreePointAdapter {
	private $joinColumn;
	private $targetJoinColumn;
	
	public function getJoinColumn() {
		return $this->joinColumn;
	}
	
	public function setJoinColumn(QueryColumn $joinColumn) {
		$this->joinColumn = $joinColumn;
	}
	
	public function getTargetJoinColumn() {
		return $this->targetJoinColumn;
	}
	
	public function setTargetJoinColumn($targetJoinColumn) {
		$this->targetJoinColumn = $targetJoinColumn;
	}
	
	public function apply(SelectStatementBuilder $selectBuilder) {
		$this->treePointMeta->applyAsJoin($selectBuilder, $this->joinType, $this->onComparator)
				->match($this->joinColumn, QueryComparator::OPERATOR_EQUAL, $this->targetJoinColumn);
		parent::apply($selectBuilder);
	}

}
