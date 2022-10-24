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

class RelationUnregisteredErrorMock extends ObjectAdapter {
	#[Id]
	private $id;

	#[OneToOne]
	private TargetMock $oneToOne;

}