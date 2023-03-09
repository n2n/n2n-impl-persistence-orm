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

		$ecm = new EmbeddedContainerMock();
		$ecm->id = 1;
		$ecm->embeddableMock = new EmbeddableMock();
		$ecm->embeddableMock->name = 'huii';
		$ecm->embeddableMock->simpleTargetMocks = new \ArrayObject([ $stm1, $stm2 ]);
		$ecm->embeddableMock->notSimpleTargetMocks = new \ArrayObject([ $stm3 ]);
		$ecm->embeddableMock->verySimpleTargetMock = $stm3;

		$tx = $tm->createTransaction();
		$em->persist($ecm);
		$tx->commit();

		$em->clear();


		$ecm = $em->find(EmbeddedContainerMock::class, 1);

		$this->assertNotNull($ecm);

		$this->assertCount(2, $ecm->embeddableMock->simpleTargetMocks);
		$this->assertCount(1, $ecm->embeddableMock->notSimpleTargetMocks);
		$this->assertNotNull($ecm->embeddableMock->verySimpleTargetMock);
	}
}
