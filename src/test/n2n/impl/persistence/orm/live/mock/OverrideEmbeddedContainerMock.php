<?php

namespace n2n\impl\persistence\orm\live\mock;


use n2n\persistence\orm\attribute\Embedded;
use n2n\persistence\orm\attribute\Id;
use n2n\persistence\orm\attribute\AttributeOverrides;
use n2n\persistence\orm\attribute\AssociationOverrides;
use n2n\persistence\orm\attribute\JoinTable;
use n2n\persistence\orm\attribute\JoinColumn;

class OverrideEmbeddedContainerMock {

	#[Id(generated: false)]
	public int $id;
	#[Embedded(columnPrefix: 'ptusch_')]
	#[AttributeOverrides(['name' => 'override_name'])]
	#[AssociationOverrides(
			joinColumnsMap: [
				'notSimpleTargetMocks' => new JoinColumn('inverse_over_oecm_id'),
				'verySimpleTargetMock' => new JoinColumn('very_over_simple_id')
			],
			joinTables: [
				'simpleTargetMocks' => new JoinTable('over_ocm_stm', 'oecm_id', 'stm_id'),
				'manySimpleTargetMocks' => new JoinTable('over_many_ocm_stm', 'moecm_id', 'mstm_id')
			])]
	public EmbeddableMock $embeddableMock;
}