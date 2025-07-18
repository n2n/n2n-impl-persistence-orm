<?php

namespace n2n\impl\persistence\orm\property\relation;

use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\test\DbTestPdoUtil;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use PHPUnit\Framework\TestCase;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\property\relation\mock\ToOneLazyEntityMock;
use n2n\persistence\orm\OrmUtils;

class ToOneLazyEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;


	public function setUp(): void {
//		$this->emm = new EntityModelManager([ScalarValueObjectEntityMock::class],
//				new EntityModelFactory([CommonEntityPropertyProvider::class]));

		$this->emPool = GeneralTestEnv::setUpEmPool([ToOneLazyEntityMock::class, SimpleTargetMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();


		$table = $metaEntityFactory->createTable('to_one_lazy_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('join_column_target_id', 32);

		$table = $metaEntityFactory->createTable('join_table');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('to_one_lazy_entity_mock_id', 32);
		$columnFactory->createIntegerColumn('simple_target_mock_id', 32);

		$table = $metaEntityFactory->createTable('simple_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('holeradio', 255);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}
	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLazyPersist() {
		$tm = $this->pdoPool->getTransactionManager();

		$entityMock = new ToOneLazyEntityMock();
		$entityMock->id = 1;
		$simpleTargetMock = new SimpleTargetMock();
		$simpleTargetMock->id = 2;
		$simpleTargetMock->holeradio = 'join column target';
		$entityMock->joinColumnTarget = $simpleTargetMock;
		$simpleTargetMock = new SimpleTargetMock();
		$simpleTargetMock->id = 3;
		$simpleTargetMock->holeradio = 'join table target';
		$entityMock->joinTableTarget = $simpleTargetMock;

		$tx = $tm->createTransaction();
		$this->tem()->persist($entityMock);
		$tx->commit();

		$tx = $tm->createTransaction(true);
		$this->assertEquals(1, $this->tem()->createCriteria()->select('COUNT(1)')
				->from(ToOneLazyEntityMock::class, 'e')->toQuery()->fetchSingle());
		$this->assertEquals(2, $this->tem()->createCriteria()->select('COUNT(1)')
				->from(SimpleTargetMock::class, 'e')->toQuery()->fetchSingle());
		$tx->commit();

		$tx = $tm->createTransaction();

		$entityMock = $this->tem()->find(ToOneLazyEntityMock::class, 1);

		$this->assertInstanceOf(SimpleTargetMock::class, $entityMock->joinColumnTarget);
		$this->assertInstanceOf(SimpleTargetMock::class, $entityMock->joinTableTarget);

		$this->assertFalse(OrmUtils::isInitialized($entityMock->joinTableTarget));
		$this->assertFalse(OrmUtils::isInitialized($entityMock->joinColumnTarget));

		$simpleTargetMock2 = $entityMock->joinColumnTarget;
		$simpleTargetMock3 = $entityMock->joinTableTarget;

		$entityMock->joinColumnTarget = $simpleTargetMock3;
		$entityMock->joinTableTarget = $simpleTargetMock2;

		$this->assertFalse(OrmUtils::isInitialized($entityMock->joinTableTarget));
		$this->assertFalse(OrmUtils::isInitialized($entityMock->joinColumnTarget));
		$tx->commit();
	}

}