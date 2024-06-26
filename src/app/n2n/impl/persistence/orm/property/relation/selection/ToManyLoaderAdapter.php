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
namespace n2n\impl\persistence\orm\property\relation\selection;

use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\spec\dbo\meta\data\SelectStatementBuilder;
use n2n\persistence\orm\query\from\TreePath;
use n2n\persistence\orm\CorruptedDataException;
use n2n\persistence\orm\store\SimpleLoaderUtils;
use n2n\spec\dbo\meta\data\QueryItem;

abstract class ToManyLoaderAdapter implements ToManyLoader {
	private $orderDirectives = array();
	
	/**
	 * 
	 * @param \n2n\impl\persistence\orm\property\relation\util\OrderDirective $orderDirectives
	 */
	public function setOrderDirectives(array $orderDirectives) {
		$this->orderDirectives = $orderDirectives;
	}
	
	/**
	 * @param MetaTreePoint $metaTreePoint
	 * @return \n2n\impl\persistence\orm\property\relation\selection\OrderQueryDirective[]
	 */
	protected function applyOrderDirectives(MetaTreePoint $metaTreePoint) {
		$directives = array();
		foreach ($this->orderDirectives as $orderDirective) {
			$queryItem = $metaTreePoint->requestPropertyRepresentableQueryItem(new TreePath($orderDirective->propertyNames));
			
			$directives[] = new OrderQueryDirective($queryItem, $orderDirective->direction);
		}	
		return $directives;
	}
	
	protected function fetchArray(SimpleLoaderUtils $utils) {
		$entityObjs = $utils->createQuery()->fetchArray();
		
		foreach ($entityObjs as $entityObj) {
			if ($entityObj === null) {
				throw new CorruptedDataException('Database contains entries of entity '
						. $utils->entityModel->getClass()->getName() . ' with id null.');
			}
		}
		
		return $entityObjs;
	}
}

class OrderQueryDirective {
	private $queryItem;
	private $direction;
	
	public function __construct(QueryItem $queryItem, string $direction) {
		$this->queryItem = $queryItem;
		$this->direction = $direction;
	}		
	
	public function apply(SelectStatementBuilder $selectBuilder) {
		$selectBuilder->addOrderBy($this->queryItem, $this->direction);
	}
}