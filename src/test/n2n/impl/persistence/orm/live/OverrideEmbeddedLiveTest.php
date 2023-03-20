<?php

namespace n2n\impl\persistence\orm\live;

use n2n\persistence\ext\PdoPool;
use n2n\core\config\DbConfig;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\sqlite\SqliteDialect;
use n2n\core\config\OrmConfig;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\core\container\TransactionManager;
use n2n\impl\persistence\orm\live\mock\EmbeddedContainerMock;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use n2n\impl\persistence\orm\live\mock\EmbeddableMock;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\live\mock\OverrideEmbeddedContainerMock;
use n2n\persistence\ext\EmPool;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModelFactory;

class OverrideEmbeddedLiveTest extends TestCase {

	private PdoPool $pdoPool;

	private EmPool $emPool;

	function setUp(): void {
		$this->pdoPool = new PdoPool(
				[PdoPool::DEFAULT_DS_NAME => new PersistenceUnitConfig('default', 'sqlite::memory:', '', '',
						PersistenceUnitConfig::TIL_SERIALIZABLE, SqliteDialect::class,
						false, null)],
				new TransactionManager(), null, null);

		$this->emPool = new EmPool($this->pdoPool,
				new EntityModelManager(
						[SimpleTargetMock::class, OverrideEmbeddedContainerMock::class],
						new EntityModelFactory([CommonEntityPropertyProvider::class])),
				$this->createMock(MagicContext::class));

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();


		$table = $metaEntityFactory->createTable('override_embedded_container_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('ptusch_override_name', 255);
		$columnFactory->createIntegerColumn('ptusch_very_over_simple_id', 32);


		$table = $metaEntityFactory->createTable('simple_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('ptusch_inverse_over_oecm_id', 32);
		$columnFactory->createStringColumn('holeradio', 255);

		$table = $metaEntityFactory->createTable('ptusch_over_ocm_stm');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('ptusch_oecm_id', 32);
		$columnFactory->createIntegerColumn('ptusch_stm_id', 32);

		$table = $metaEntityFactory->createTable('ptusch_over_many_ocm_stm');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('ptusch_moecm_id', 32);
		$columnFactory->createIntegerColumn('ptusch_mstm_id', 32);


		$metaData->getMetaManager()->flush();
	}

	function testNotFound() {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$this->assertNull($em->find(OverrideEmbeddedContainerMock::class, 1));
	}

	function testPersist() {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();
		$tm = $this->pdoPool->getTransactionManager();

		$stm1 = new SimpleTargetMock();
		$stm1->id = 1;
		$stm1->holeradio = 'huii';

		$stm2 = new SimpleTargetMock();
		$stm2->id = 2;
		$stm2->holeradio = 'huii 2';

		$stm3 = new SimpleTargetMock();
		$stm3->id = 3;
		$stm3->holeradio = 'huii 3';

		$stm4 = new SimpleTargetMock();
		$stm4->id = 4;
		$stm4->holeradio = 'huii 4';

		$stm5 = new SimpleTargetMock();
		$stm5->id = 5;
		$stm5->holeradio = 'huii 5';

		$ecm = new OverrideEmbeddedContainerMock();
		$ecm->id = 1;
		$ecm->embeddableMock = new EmbeddableMock();
		$ecm->embeddableMock->name = 'huii';
		$ecm->embeddableMock->simpleTargetMocks = new \ArrayObject([ $stm1, $stm2 ]);
		$ecm->embeddableMock->notSimpleTargetMocks = new \ArrayObject([ $stm3 ]);
		$ecm->embeddableMock->verySimpleTargetMock = $stm3;
		$ecm->embeddableMock->manySimpleTargetMocks = new \ArrayObject([ $stm4, $stm5 ]);

		$tx = $tm->createTransaction();
		$em->persist($ecm);
		$tx->commit();

		$this->assertEquals(1, $this->countEntityObjs($em, OverrideEmbeddedContainerMock::class));
		$this->assertEquals(5, $this->countEntityObjs($em, SimpleTargetMock::class));

		$em->clear();

		$ecm2 = $em->find(OverrideEmbeddedContainerMock::class, 1);

		$this->assertNotNull($ecm2);

		$this->assertEquals($ecm->embeddableMock->simpleTargetMocks, $ecm2->embeddableMock->simpleTargetMocks);
		$this->assertEquals($ecm->embeddableMock->notSimpleTargetMocks, $ecm2->embeddableMock->notSimpleTargetMocks);
		$this->assertEquals($ecm->embeddableMock->verySimpleTargetMock, $ecm2->embeddableMock->verySimpleTargetMock);
		$this->assertEquals($ecm->embeddableMock->manySimpleTargetMocks, $ecm2->embeddableMock->manySimpleTargetMocks);

		$tx = $tm->createTransaction();
		$em->remove($ecm2);
		$tx->commit();


		$this->assertEmpty($this->countEntityObjs($em, OverrideEmbeddedContainerMock::class));
		$this->assertEmpty($this->countEntityObjs($em, SimpleTargetMock::class));
	}

	private function countEntityObjs(EntityManager $em, string $entityClass): int {
		return $em->createCriteria()->select('COUNT(1)')->from($entityClass, 'e')
				->toQuery()->fetchSingle();
	}
}
