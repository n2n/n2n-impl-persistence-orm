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
namespace n2n\impl\persistence\orm\property\select;

use n2n\spec\dbo\meta\data\QueryItem;
use n2n\persistence\PdoStatement;
use n2n\l10n\N2nLocale;
use n2n\persistence\orm\CorruptedDataException;
use n2n\l10n\IllegalN2nLocaleFormatException;
use n2n\persistence\orm\query\select\ValueBuilder;
use n2n\persistence\orm\query\select\Selection;
use n2n\persistence\orm\query\select\EagerValueBuilder;

class N2nLocaleSelection implements Selection {
	private $queryItem;
	private $ormDialectConfig;
	private $value;

	public function __construct(QueryItem $queryItem) {
		$this->queryItem = $queryItem;
	}
	
	public function getSelectQueryItems(): array {
		return array($this->queryItem);
	}

	public function bindColumns(PdoStatement $stmt, array $columnAliases): void {
		$stmt->shareBindColumn($columnAliases[0], $this->value);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\query\select\Selection::createValueBuilder()
	 */
	public function createValueBuilder(): ValueBuilder {
		try {
			return new EagerValueBuilder(N2nLocale::build($this->value));
		} catch (IllegalN2nLocaleFormatException $e) {
			throw new CorruptedDataException(null, 0, $e);
		}
	}

}
