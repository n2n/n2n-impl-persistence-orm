<?php

namespace n2n\impl\persistence\orm\property\relation\selection;

use n2n\persistence\orm\proxy\ProxyAccessListener;
use n2n\util\type\ArgUtils;

class ArrayObjectProxyAccessListener implements ProxyAccessListener {



	function onAccess(object $obj): void {
		assert($obj instanceof \ArrayObject);
		$obj->__construct($that->toManyLoader->loadEntities($this->id));
	}

	function dispose(): void {
		// TODO: Implement dispose() method.
	}

	function getId(): mixed {
		return null;
	}
}