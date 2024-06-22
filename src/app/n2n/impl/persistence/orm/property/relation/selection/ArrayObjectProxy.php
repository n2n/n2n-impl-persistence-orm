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

use n2n\persistence\orm\property\EntityProperty;
use n2n\util\ex\IllegalStateException;

class ArrayObjectProxy extends \ArrayObject {
	private $loadClosure;
//	private $targetIdEntityProperty;
	private $id;
// 	private $loadedValueHash;
	private \SplObjectStorage $initializedListeners;
	private \WeakMap $weakRefInitializedListenerMap;

	public function __construct(\Closure $loadClosure, EntityProperty $targetIdEntityProperty) {
		parent::__construct();

		$this->initializedListeners = new \SplObjectStorage();
		$this->weakRefInitializedListenerMap = new \WeakMap();
		$this->loadClosure = new \ReflectionFunction($loadClosure);
//		$this->targetIdEntityProperty = $targetIdEntityProperty;
		$this->id = uniqid();


	}

	public function getId() {
		return $this->id;
	}

// 	public function getLoadedValueHash() {
// 		IllegalStateException::assertTrue($this->loadedValueHash !== null);
// 		return $this->loadedValueHash;
// 	}

	public function isInitialized() {
		return $this->loadClosure === null;
	}

	public function initialize() {
		if ($this->isInitialized()) return;

		$entities = $this->loadClosure->invoke();
// 		$hasher = new ToManyValueHasher($this->targetIdEntityProperty);
// 		$this->loadedValueHash = $hasher->createValueHash($entities);
		parent::exchangeArray($entities);
		$this->loadClosure = null;

		foreach ($this->initializedListeners as $initializedListener) {
			$initializedListener->arrayObjectProxyInitialized();
		}
		$this->initializedListeners = new \SplObjectStorage();

		foreach ($this->weakRefInitializedListenerMap as $listener => $value) {
			$listener->arrayObjectProxyInitialized();
		}
		$this->weakRefInitializedListenerMap = new \WeakMap();
	}

	function registerInitializedListener(ArrayObjectInitializedListener $initializedListener) {
		IllegalStateException::assertTrue(!$this->isInitialized());
		$this->initializedListeners->attach($initializedListener);
	}

	public function registerArrayObjectInitializedListener(ArrayObjectInitializedListener $initializedListener) {
		IllegalStateException::assertTrue(!$this->isInitialized());
		$this->weakRefInitializedListenerMap[$initializedListener] = null;
	}

	public function offsetExists ($index): bool {
		$this->initialize();
		return parent::offsetExists($index);
	}

	public function offsetGet ($index): mixed {
		$this->initialize();
		return parent::offsetGet($index);
	}

	public function offsetSet ($index, $newval): void {
		$this->initialize();
		parent::offsetSet($index, $newval);
	}

	public function offsetUnset ($index): void {
		$this->initialize();
		parent::offsetUnset($index);
	}

	public function append ($value): void {
		$this->initialize();
		parent::append($value);
	}

	public function getArrayCopy (): array {
		$this->initialize();
		return parent::getArrayCopy();
	}

	public function count (): int {
		$this->initialize();
		return parent::count();
	}

	#[\ReturnTypeWillChange]
	public function asort (int $flags = SORT_REGULAR): bool {
		$this->initialize();
		return parent::asort($flags);
	}

	#[\ReturnTypeWillChange]
	public function ksort (int $flags = SORT_REGULAR): bool {
		$this->initialize();
		return parent::ksort($flags);
	}

	#[\ReturnTypeWillChange]
	public function uasort ($cmp_function): bool {
		$this->initialize();
		return parent::uasort($cmp_function);
	}

	#[\ReturnTypeWillChange]
	public function uksort ($cmp_function): bool {
		$this->initialize();
		return parent::uksort($cmp_function);
	}

	#[\ReturnTypeWillChange]
	public function natsort (): bool {
		$this->initialize();
		return parent::natsort();
	}

	#[\ReturnTypeWillChange]
	public function natcasesort (): bool {
		$this->initialize();
		return parent::natcasesort();
	}

	public function serialize (): string {
		$this->initialize();
		return parent::serialize();
	}

	public function getIterator (): \Iterator {
		$this->initialize();
		return parent::getIterator();
	}

	public function exchangeArray ($input): array {
		$this->initialize();
		return parent::exchangeArray($input);
	}

	public function setIteratorClass ($iterator_class): void {
		$this->initialize();
		parent::setIteratorClass($iterator_class);
	}

	public function getIteratorClass (): string {
		$this->initialize();
		return parent::getIteratorClass();
	}
	/* (non-PHPdoc)
	 * @see Serializable::unserialize()
	 */
// 	public function unserialize($serialized) {
// 		$this->initialize();
// 		return parent::unserialize($serialized);
// 	}

}
