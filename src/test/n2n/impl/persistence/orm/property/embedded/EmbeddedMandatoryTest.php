<?php

namespace n2n\impl\persistence\orm\property\embedded;

use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\impl\persistence\orm\property\embedded\mock\EmbeddedContainerManMock;
use n2n\impl\persistence\orm\property\embedded\mock\EmbeddableManMock;
use n2n\persistence\orm\EntityManager;
use PHPUnit\Framework\TestCase;

class EmbeddedMandatoryTest extends TestCase {

	private EmPool $emPool;
	private PdoPool $pdoPool;
//	private LifecycleListener $lifecycleListener;

	function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([EmbeddedContainerManMock::class, EmbeddableManMock::class]);
//		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('embedded_container_man_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('mandatory_holeradio', 255);
		$columnFactory->createIntegerColumn('optional_holeradio', 255);

		$metaData->getMetaManager()->flush();
	}


	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testNullPersist(): void {
		$tm = $this->pdoPool->getTransactionManager();

		$ecm = new EmbeddedContainerManMock();
		$ecm->id = 1;

		$tx = $tm->createTransaction();
		$this->tem()->persist($ecm);
		$tx->commit();

		$tx = $tm->createTransaction(true);
		$ecm = $this->tem()->find(EmbeddedContainerManMock::class, $ecm->id);
		$tx->commit();

		$this->assertNotNull($ecm->mandatoryEmbeddableMock);
		$this->assertNull($ecm->optionalEmbeddableMock);
	}

}