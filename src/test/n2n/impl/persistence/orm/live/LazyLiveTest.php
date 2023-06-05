<?php

namespace n2n\impl\persistence\orm\live;

use PHPUnit\Framework\TestCase;
use n2n\persistence\ext\PdoPool;
use n2n\core\config\DbConfig;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\sqlite\SqliteDialect;
use n2n\core\config\OrmConfig;
use n2n\impl\persistence\orm\live\mock\EmbeddedContainerMock;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use n2n\impl\persistence\orm\live\mock\OverrideEmbeddedContainerMock;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\util\magic\MagicContext;
use n2n\core\container\TransactionManager;
use n2n\impl\persistence\orm\live\mock\LazyContainerMock;
use n2n\impl\persistence\orm\property\relation\selection\ArrayObjectProxy;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\persistence\orm\OrmUtils;
use n2n\impl\persistence\orm\property\relation\util\ArrayObjectProxyUtils;

class LazyLiveTest extends TestCase {


	function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([LazyContainerMock::class, SimpleTargetMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('lazy_container_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('holeradio', 255);

		$table = $metaEntityFactory->createTable('simple_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('lazy_container_mock_id', 32);
		$columnFactory->createStringColumn('holeradio', 255);

		$metaData->getMetaManager()->flush();
	}

	function testLazyLoad() {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$this->assertNull($em->find(LazyContainerMock::class, 1));

		$tm = $this->pdoPool->getTransactionManager();


		$stm1 = new SimpleTargetMock();
		$stm1->id = 1;
		$stm1->holeradio = 'huii';

		$lcm = new LazyContainerMock(1);
		$lcm->setSimpleTargetMocks(new \ArrayObject([$stm1]));

		$tx = $tm->createTransaction();
		$em->persist($lcm);
		$tx->commit();

		$em->clear();


		$this->assertNotNull($lcm->getId());

		$reLcm = $em->find(LazyContainerMock::class, 1);
		$this->assertNotNull($reLcm);
		$this->assertFalse($lcm === $reLcm);

		$this->assertInstanceOf(ArrayObjectProxy::class, $reLcm->getSimpleTargetMocks());
		$calls = 0;
		ArrayObjectProxyUtils::whenInitialized($reLcm->getSimpleTargetMocks(), function () use (&$calls) {
			$calls++;
		});

		$this->assertFalse($reLcm->getSimpleTargetMocks()->isInitialized());
		$this->assertEquals(0, $calls);

		$this->assertCount(1, $reLcm->getSimpleTargetMocks());

		$this->assertTrue($reLcm->getSimpleTargetMocks()->isInitialized());
		$this->assertEquals(1, $calls);

		ArrayObjectProxyUtils::whenInitialized($reLcm->getSimpleTargetMocks(), function () use (&$calls) {
			$calls++;
		});
		$this->assertEquals(2, $calls);

	}

}