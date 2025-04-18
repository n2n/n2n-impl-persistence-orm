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
namespace n2n\impl\persistence\orm\property\relation\util;

/**
 * Accepts an {@link ArrayObject} / {@link ArrayObjectProxy} and retains the original field values of it but not until
 * the ArrayObjectProxy was initialized or {@link self::getEntityObjs()}/{@link self::update()}/{@link self::getThenUpdateEntityObjs()} was called.
 */
class ArrayObjectState {

	/**
	 * @var object[]|null
	 */
	private ?array $entityObjs = null;

	function __construct(private \ArrayObject $groupArrayObject) {
		ArrayObjectProxyUtils::whenInitialized($this->groupArrayObject,
				fn() => $this->entityObjs = $this->groupArrayObject->getArrayCopy());
	}

	/**
	 * @return object[]
	 */
	function getEntityObjs(): array {
		return $this->entityObjs ?? $this->entityObjs = $this->groupArrayObject->getArrayCopy();
	}

	function update(): void {
		$this->entityObjs = $this->groupArrayObject->getArrayCopy();
	}

	function getThenUpdateEntityObjs(): array {
		$entityObjs = $this->getEntityObjs();
		$this->update();
		return $entityObjs;
	}

}