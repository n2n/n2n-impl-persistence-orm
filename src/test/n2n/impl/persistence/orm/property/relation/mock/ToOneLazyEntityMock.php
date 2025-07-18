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
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\JoinTable;
use n2n\persistence\orm\FetchType;


#[EntityListeners(LifecycleListener::class)]
class ToOneLazyEntityMock {
	#[Id(generated: false)]
	public int $id;

	#[ManyToOne(SimpleTargetMock::class, cascade: CascadeType::ALL, fetch: FetchType::LAZY)]
	#[JoinTable('join_table')]
	public ?SimpleTargetMock $joinTableTarget = null;

	#[ManyToOne(SimpleTargetMock::class, cascade: CascadeType::ALL, fetch: FetchType::LAZY)]
	public ?SimpleTargetMock $joinColumnTarget = null;

}