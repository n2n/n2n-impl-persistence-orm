<?php

namespace n2n\impl\persistence\orm\property\class;

use n2n\persistence\orm\attribute\ManyToMany;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\Embedded;
use n2n\persistence\orm\attribute\AssociationOverrides;
use n2n\persistence\orm\attribute\Column;
use n2n\persistence\orm\attribute\AttributeOverrides;
use n2n\persistence\orm\attribute\DateTime;
use n2n\persistence\orm\attribute\DiscriminatorColumn;
use n2n\persistence\orm\attribute\Url;
use n2n\persistence\orm\attribute\Transient;
use n2n\persistence\orm\attribute\Table;
use n2n\persistence\orm\attribute\OrderBy;
use n2n\persistence\orm\attribute\NamingStrategy;
use n2n\persistence\orm\attribute\N2nLocale;
use n2n\persistence\orm\attribute\MappedSuperclass;
use n2n\persistence\orm\attribute\DiscriminatorValue;
use n2n\persistence\orm\attribute\File;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\Inheritance;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\JoinTable;
use n2n\persistence\orm\attribute\ManagedFile;
use n2n\persistence\orm\attribute\EntityListeners;

#[DiscriminatorColumn('discColumn')]
#[DiscriminatorValue('discValue')]
class PersistenceTestClass {

	#[AssociationOverrides([], [])]
	private $associationOverrides;
	#[AttributeOverrides([])]
	private $attributeOverrides;
	#[Column('differentColumn')]
	private $column;
	#[DateTime()]
	private $dateTime;

	private $discriminatorColumn;
	private $discriminatorValue;

	#[Embedded(TargetClassTest::class)]
	private $embedded;

	#[EntityListeners(EntityListener::class)]
	private $entityListeners;
	#[File()]
	private $file;
	#[Id()]
	private $id;
	#[Inheritance()]
	private $inheritance;
	#[JoinColumn()]
	private $joinColumn;
	#[JoinTable()]
	private $joinTable;
	#[ManagedFile()]
	private $managedFile;
	#[ManyToMany()]
	private $manyToMany;
	#[ManyToOne()]
	private $manyToOne;
	#[MappedSuperclass()]
	private $mappedSuperclass;
	#[N2nLocale()]
	private $n2nLocale;
	#[NamingStrategy()]
	private $namingStrategy;
	#[OneToMany()]
	private $oneToMany;
	#[OneToOne()]
	private $oneToOne;
	#[OrderBy()]
	private $orderBy;
	#[Table()]
	private $table;
	#[Transient()]
	private $transient;
	#[Url()]
	private $url;


}