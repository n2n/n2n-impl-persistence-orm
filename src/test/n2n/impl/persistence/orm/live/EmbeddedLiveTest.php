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

class EmbeddedLiveTest extends TestCase {

	private PdoPool $pdoPool;

	function setUp(): void {
		$this->pdoPool = new PdoPool(
				new DbConfig([new PersistenceUnitConfig('default', 'sqlite::memory:', '', '',
						PersistenceUnitConfig::TIL_SERIALIZABLE, SqliteDialect::class,
						false, null)]),
				new OrmConfig([EmbeddedContainerMock::class, SimpleTargetMock::class], [CommonEntityPropertyProvider::class]),
				$this->createMock(MagicContext::class),
				new TransactionManager(), null, null);

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('embedded_container_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('holeradio_name', 255);
		$columnFactory->createIntegerColumn('holeradio_very_simple_target_mock_id', 32);


		$table = $metaEntityFactory->createTable('simple_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('holeradio_embeddable_mock_id', 32);
		$columnFactory->createStringColumn('holeradio', 255);

		$table = $metaEntityFactory->createTable('holeradio_embedded_container_mock_not_simple_target_mocks');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('holeradio_embedded_container_mock_id', 32);
		$columnFactory->createIntegerColumn('holeradio_simple_target_mock_id', 32);

		$table = $metaEntityFactory->createTable('holeradio_embedded_container_mock_many_simple_target_mocks');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('holeradio_embedded_container_mock_id', 32);
		$columnFactory->createIntegerColumn('holeradio_simple_target_mock_id', 32);


		$metaData->getMetaManager()->flush();
	}

	function testNotFound() {
		$em = $this->pdoPool->getEntityManagerFactory()->getExtended();

		$this->assertNull($em->find(EmbeddedContainerMock::class, 1));
	}

	function testPersist() {
		$em = $this->pdoPool->getEntityManagerFactory()->getExtended();
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

		$ecm = new EmbeddedContainerMock();
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

		$this->assertEquals(1, $this->countEntityObjs($em, EmbeddedContainerMock::class));
		$this->assertEquals(5, $this->countEntityObjs($em, SimpleTargetMock::class));

		$em->clear();

		$ecm2 = $em->find(EmbeddedContainerMock::class, 1);

		$this->assertNotNull($ecm2);

		$this->assertEquals($ecm->embeddableMock->simpleTargetMocks, $ecm2->embeddableMock->simpleTargetMocks);
		$this->assertEquals($ecm->embeddableMock->notSimpleTargetMocks, $ecm2->embeddableMock->notSimpleTargetMocks);
		$this->assertEquals($ecm->embeddableMock->verySimpleTargetMock, $ecm2->embeddableMock->verySimpleTargetMock);
		$this->assertEquals($ecm->embeddableMock->manySimpleTargetMocks, $ecm2->embeddableMock->manySimpleTargetMocks);

		$tx = $tm->createTransaction();
		$em->remove($ecm2);
		$tx->commit();


		$this->assertEmpty($this->countEntityObjs($em, EmbeddedContainerMock::class));
		$this->assertEmpty($this->countEntityObjs($em, SimpleTargetMock::class));
	}

	private function countEntityObjs(EntityManager $em, string $entityClass): int {
		return $em->createCriteria()->select('COUNT(1)')->from($entityClass, 'e')
				->toQuery()->fetchSingle();
	}
}
