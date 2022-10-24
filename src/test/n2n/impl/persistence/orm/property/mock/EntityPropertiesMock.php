<?php
namespace n2n\impl\persistence\orm\property\mock;

use n2n\persistence\orm\attribute\ManyToMany;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\Embedded;
use n2n\persistence\orm\attribute\AssociationOverrides;
use n2n\persistence\orm\attribute\Column;
use n2n\persistence\orm\attribute\AttributeOverrides;
use n2n\persistence\orm\attribute\Url;
use n2n\persistence\orm\attribute\Transient;
use n2n\persistence\orm\attribute\OrderBy;
use n2n\persistence\orm\attribute\N2nLocale;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\JoinTable;
use n2n\persistence\orm\attribute\ManagedFile;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\persistence\orm\attribute\DateTime;
use n2n\io\managed\FileManager;
use n2n\reflection\ObjectAdapter;
use n2n\impl\persistence\orm\property\class\EntityListener;

class EntityPropertiesMock extends ObjectAdapter {
	#[Id]
	private int $id;

	#[ManyToOne]
	#[JoinTable('join_table', 'persistence_test_class_id', 'test_id')]
	private TargetMock $joinTable;

	#[ManyToMany(TargetMock::class)]
	#[JoinTable('join_tables', 'table_ids', 'test_ids')]
	private $joinTables;

	#[OneToOne]
	#[JoinColumn('join_column')]
	private TargetMock $joinColumn;

	#[AssociationOverrides(['column1'], ['table1'])]
	private $associationOverrides;
	#[AttributeOverrides([])]
	private $attributeOverrides;
	#[Column('differentColumn')]
	private string $column;

	#[DateTime]
	private $dateTime;

	#[Embedded]
	private EmbeddedMock $embedded;

	#[EntityListeners(EntityListener::class)]
	private $entityListeners;

	#[ManagedFile(FileManager::TYPE_PRIVATE)]
	private $managedFile;
	#[ManyToMany(TargetMock::class)]
	private \ArrayObject $manyToMany;
	#[ManyToOne]
	private TargetMock $manyToOne;
	#[N2nLocale]
	private $n2nLocale;
	#[OneToMany(TargetMock::class)]
	private \ArrayObject $oneToMany;
	#[OneToOne]
	private TargetMock $oneToOne;
	#[OrderBy(['orderIndex' => 'ASC'])]
	private $orderBy;
	#[Transient]
	private $transient;
	#[Url]
	private $url;
}