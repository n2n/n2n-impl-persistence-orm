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

use n2n\impl\persistence\orm\property\relation\selection\ArrayObjectProxy;
use n2n\impl\persistence\orm\property\relation\selection\ArrayObjectInitializedListener;

class ArrayObjectProxyUtils {

	static function initialize(\ArrayObject|ArrayObjectProxy $arrayObject): void {
		if ($arrayObject instanceof ArrayObjectProxy) {
			$arrayObject->initialize();
		}
	}

	static function isInitialized(\ArrayObject|ArrayObjectProxy $arrayObject): bool {
		return !($arrayObject instanceof ArrayObjectProxy) || $arrayObject->isInitialized();
	}

	static function whenInitialized(\ArrayObject|ArrayObjectProxy $arrayObject, \Closure $callback): void {
		if (!($arrayObject instanceof ArrayObjectProxy) || $arrayObject->isInitialized()) {
			$callback();
			return;
		}

		$arrayObject->registerInitializedListener(new class($callback) implements ArrayObjectInitializedListener {
			function __construct(private \Closure $callback) {

			}

			function arrayObjectProxyInitialized(): void {
				$callback = $this->callback;
				$callback();
			}
		});
	}

	/**
	 * @see ArrayObjectState for docs.
	 */
	static function state(\ArrayObject|ArrayObjectProxy $arrayObject): ArrayObjectState {
		return new ArrayObjectState($arrayObject);
	}
}

