<?php

namespace n2n\impl\persistence\orm\property\valobj\mock;

use n2n\spec\valobj\scalar\IntValueObject;
use n2n\spec\valobj\err\IllegalValueException;
use n2n\spec\valobj\scalar\StringValueObject;

class ShortString implements StringValueObject {

	/**
	 * @inheritDoc
	 */
	public function __construct(private string $value) {
		IllegalValueException::assertTrue(mb_strlen($this->value) <= 5);
	}

	function toScalar(): string {
		return $this->value;
	}
}