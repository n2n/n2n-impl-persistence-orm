<?php
namespace n2n\impl\persistence\orm\property\mock;

use n2n\persistence\orm\attribute\DateTime;
use n2n\persistence\orm\attribute\Id;
use n2n\reflection\ObjectAdapter;

/**
 * Test entity to verify DateTimeInterface compatibility with ORM
 */
class DateTimeInterfaceMock extends ObjectAdapter {
	#[Id]
	private $id;

	/**
	 * Property with DateTime type annotation
	 */
	#[DateTime]
	private \DateTimeInterface $dateTimeProperty;

	/**
	 * Property with DateTimeImmutable type annotation
	 */
	#[DateTime]
	private \DateTimeInterface $dateTimeImmutableProperty;

	/**
	 * Property with DateTimeInterface type annotation
	 */
	#[DateTime]
	private \DateTimeInterface $dateTimeInterfaceProperty;

	/**
	 * Nullable DateTime property
	 */
	#[DateTime]
	private ?\DateTimeInterface $nullableDateTimeProperty = null;

	/**
	 * Nullable DateTimeImmutable property
	 */
	#[DateTime]
	private ?\DateTimeInterface $nullableDateTimeImmutableProperty = null;

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getDateTimeProperty(): \DateTimeInterface {
		return $this->dateTimeProperty;
	}

	public function setDateTimeProperty(\DateTimeInterface $dateTimeProperty) {
		$this->dateTimeProperty = $dateTimeProperty;
	}

	public function getDateTimeImmutableProperty(): \DateTimeInterface {
		return $this->dateTimeImmutableProperty;
	}

	public function setDateTimeImmutableProperty(\DateTimeInterface $dateTimeImmutableProperty) {
		$this->dateTimeImmutableProperty = $dateTimeImmutableProperty;
	}

	public function getDateTimeInterfaceProperty(): \DateTimeInterface {
		return $this->dateTimeInterfaceProperty;
	}

	public function setDateTimeInterfaceProperty(\DateTimeInterface $dateTimeInterfaceProperty) {
		$this->dateTimeInterfaceProperty = $dateTimeInterfaceProperty;
	}

	public function getNullableDateTimeProperty(): ?\DateTimeInterface {
		return $this->nullableDateTimeProperty;
	}

	public function setNullableDateTimeProperty(?\DateTimeInterface $nullableDateTimeProperty) {
		$this->nullableDateTimeProperty = $nullableDateTimeProperty;
	}

	public function getNullableDateTimeImmutableProperty(): ?\DateTimeInterface {
		return $this->nullableDateTimeImmutableProperty;
	}

	public function setNullableDateTimeImmutableProperty(?\DateTimeInterface $nullableDateTimeImmutableProperty) {
		$this->nullableDateTimeImmutableProperty = $nullableDateTimeImmutableProperty;
	}
} 