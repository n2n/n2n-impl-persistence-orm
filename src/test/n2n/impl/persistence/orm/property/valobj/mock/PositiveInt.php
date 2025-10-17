<?php

namespace n2n\impl\persistence\orm\property\valobj\mock;

use n2n\spec\valobj\scalar\IntValueObject;
use n2n\spec\valobj\err\IllegalValueException;

class PositiveInt implements IntValueObject, \Stringable {

	/**
	 * @inheritDoc
	 */
	public function __construct(private int $value) {
		IllegalValueException::assertTrue($this->value >= 0);
	}

	function toScalar(): int {
		return $this->value;
	}

	function __toString(): string {
		return $this->value;
	}
}