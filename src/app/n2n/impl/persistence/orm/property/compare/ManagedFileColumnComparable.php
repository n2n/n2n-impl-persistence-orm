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

use n2n\spec\dbo\meta\data\QueryItem;
use n2n\persistence\orm\query\QueryState;
use n2n\util\type\TypeConstraint;
use n2n\util\type\ArgUtils;
use n2n\spec\dbo\meta\data\impl\QueryPlaceMarker;
use n2n\io\managed\FileManager;
use n2n\io\managed\File;
use n2n\persistence\orm\criteria\CriteriaConflictException;
use n2n\persistence\meta\data\QueryPartGroup;
use n2n\persistence\orm\criteria\compare\ColumnComparableAdapter;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;

class ManagedFileColumnComparable extends ColumnComparableAdapter {
	private $queryState;
	private $fileManager;
	
	public function __construct(QueryItem $comparableQueryItem, QueryState $queryState, FileManager $fileManager) {
		parent::__construct(CriteriaComparator::getOperators(false), 
				TypeConstraint::createSimple('n2n\io\managed\File', true), $comparableQueryItem);
		
		$this->queryState = $queryState;
		$this->fileManager = $fileManager;
	}
	
	public function buildCounterpartQueryItemFromValue(string $operator, mixed $value): QueryItem {
		if ($operator != CriteriaComparator::OPERATOR_IN && $operator != CriteriaComparator::OPERATOR_NOT_IN) {
			ArgUtils::valType($value, 'n2n\io\managed\File', true);
			return new QueryPlaceMarker($this->registerPlaceholder($value));
		}
	
		ArgUtils::valArray($value, 'n2n\io\managed\File');
	
		$queryPartGroup = new QueryPartGroup();
		foreach ($value as $fieldValue) {
			$queryPartGroup->addQueryPart(new QueryPlaceMarker($this->registerPlaceholder($fieldValue)));
		}
		return $queryPartGroup;
	}
	
	private function registerPlaceholder(?File $file = null)  {
		if ($file === null) {
			return $this->queryState->registerPlaceholderValue(null);
		}
		
		if (null !== ($qualifiedName = $this->fileManager->checkFile($file))) {
			return $this->queryState->registerPlaceholderValue($qualifiedName);
		}
		
		// @todo better exception
		throw new CriteriaConflictException('Passed file is not registered in file manager.');
	}
	
	public function buildCounterpartPlaceholder($operator, $value) {
	
	}
}
