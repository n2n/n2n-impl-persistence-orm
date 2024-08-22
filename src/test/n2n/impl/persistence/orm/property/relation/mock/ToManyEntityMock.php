<?php

namespace n2n\impl\persistence\orm\property\relation\mock;

use n2n\persistence\orm\model\EntityModelManager;
use n2n\impl\persistence\orm\property\mock\EntityPropertiesMock;
use n2n\impl\persistence\orm\property\mock\TargetMock;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\persistence\orm\attribute\Id;
use n2n\impl\persistence\orm\property\valobj\mock\PositiveInt;
use n2n\persistence\orm\attribute\ManyToMany;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use ArrayObject;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\persistence\orm\CascadeType;


#[EntityListeners(LifecycleListener::class)]
class ToManyEntityMock {
	#[Id(generated: false)]
	public int $id;

	#[ManyToMany(SimpleTargetMock::class, cascade: CascadeType::ALL)]
	public \ArrayObject $joinTableTargets;

	#[OneToMany(SimpleTargetMock::class, cascade: CascadeType::ALL)]
	#[JoinColumn('inverse_join_column_id')]
	public \ArrayObject $inverseJoinColumnTargets;

	function __construct() {
		$this->joinTableTargets = new ArrayObject();
		$this->inverseJoinColumnTargets = new ArrayObject();
	}
}