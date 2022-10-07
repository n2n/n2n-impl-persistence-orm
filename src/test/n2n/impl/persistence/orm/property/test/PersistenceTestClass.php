<?php
namespace n2n\impl\persistence\orm\property\test;

use n2n\persistence\orm\attribute\ManyToMany;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\Embedded;
use n2n\persistence\orm\attribute\AssociationOverrides;
use n2n\persistence\orm\attribute\Column;
use n2n\persistence\orm\attribute\AttributeOverrides;
use n2n\persistence\orm\attribute\DiscriminatorColumn;
use n2n\persistence\orm\attribute\Url;
use n2n\persistence\orm\attribute\Transient;
use n2n\persistence\orm\attribute\Table;
use n2n\persistence\orm\attribute\OrderBy;
use n2n\persistence\orm\attribute\NamingStrategy;
use n2n\persistence\orm\attribute\N2nLocale;
use n2n\persistence\orm\attribute\DiscriminatorValue;
use n2n\persistence\orm\attribute\File;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\Inheritance;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\JoinTable;
use n2n\persistence\orm\attribute\ManagedFile;
use n2n\persistence\orm\attribute\EntityListeners;
use n2n\persistence\orm\attribute\DateTime;
use n2n\persistence\orm\InheritanceType;
use n2n\io\managed\FileManager;
use n2n\persistence\orm\attribute\MappedSuperclass;
use n2n\persistence\orm\model\HyphenatedNamingStrategy;
use n2n\reflection\ObjectAdapter;

#[DiscriminatorColumn('discColumn')]
#[DiscriminatorValue('discValue')]
#[Table('persistence_test_class')]
#[Inheritance(InheritanceType::SINGLE_TABLE)]
#[MappedSuperclass]
#[NamingStrategy(HyphenatedNamingStrategy::class)]
class PersistenceTestClass extends ObjectAdapter {

	#[AssociationOverrides([], [])]
	private $associationOverrides;
	#[AttributeOverrides([])]
	private $attributeOverrides;
	#[Column('differentColumn')]
	private string $column;

	#[DateTime, JoinColumn('holeradio_date_time')]
	private $dateTime;

	private $discriminatorColumn;
	private $discriminatorValue;

	#[Embedded(TargetClassTest::class)]
	private $embedded;

	#[EntityListeners([EntityListener::class])]
	private $entityListeners;
	#[File]
	private $file;
	#[Id(false, '[sequence here]')]
	private $id;
	#[JoinTable('join_table', 'persistence_test_class_id')]
	private $joinTable;
	#[ManagedFile(FileManager::TYPE_PRIVATE)]
	private $managedFile;
	#[ManyToMany(TargetClassTest::class)]
	private $manyToMany;
	#[ManyToOne(TargetClassTest::class)]
	private $manyToOne;
	#[N2nLocale]
	private $n2nLocale;
	#[OneToMany(TargetClassTest::class)]
	private $oneToMany;
	#[OneToOne(TargetClassTest::class)]
	private $oneToOne;
	#[OrderBy(['orderIndex' => 'ASC'])]
	private $orderBy;
	#[Transient]
	private $transient;
	#[Url]
	private $url;
}