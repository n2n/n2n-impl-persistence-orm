<?php

namespace n2n\impl\persistence\orm\live;

use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\impl\persistence\orm\live\mock\ObservableTestObj;
use n2n\impl\persistence\orm\live\mock\ObservableTargetTestObj;
use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\property\relation\util\ArrayObjectProxyUtils;
use n2n\persistence\orm\OrmUtils;
use n2n\impl\persistence\orm\live\mock\OttoEntityListenerMock;
use n2n\persistence\orm\model\mock\EntityListenerMock;
use n2n\test\TestEnv;
use n2n\core\util\N2nUtil;
use n2n\core\util\ClosureCommitListener;

class EntityListenerLiveTest extends TestCase {

	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;

	private OttoEntityListenerMock $ottoEntityListenerMock;

	function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([ObservableTestObj::class, ObservableTargetTestObj::class],
				[OttoEntityListenerMock::class => $this->ottoEntityListenerMock = new OttoEntityListenerMock()]);
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('observable_test_obj');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('observable_target_test_obj_id', 32);

		$table = $metaEntityFactory->createTable('observable_target_test_obj');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('holeradio', 255);
		$columnFactory->createIntegerColumn('observable_test_obj_id', 32);

		$metaData->getMetaManager()->flush();
	}

	function testLifecycle(): void {
		$tm = $this->pdoPool->getTransactionManager();
		$tx = $tm->createTransaction();

		$tem = $this->emPool->getEntityManagerFactory()->getTransactional();

		$otto1 = new ObservableTargetTestObj(1);
		$otto1->setHoleradio('huii 2');

		$otto2 = new ObservableTargetTestObj(2);
		$otto2->setHoleradio('huii 2');

		$oto1 = new ObservableTestObj(1);
		$oto1->setObservableTargetTestObjs(new \ArrayObject([$otto1]));
		$oto1->setObservableTargetTestObj($otto2);

		$tem->persist($oto1);

		$this->assertCount(0, $oto1->getMethodCalls());

		$tx->commit();

		$this->assertCount(2, $oto1->getMethodCalls());
		$this->assertEquals('_prePersist', $oto1->getMethodCalls()[0]);
		$this->assertEquals('_postPersist', $oto1->getMethodCalls()[1]);

		$this->assertCount(2, $otto1->getMethodCalls());
		$this->assertEquals('_prePersist', $otto1->getMethodCalls()[0]);
		$this->assertEquals('_postPersist', $otto1->getMethodCalls()[1]);

		$this->assertCount(2, $otto2->getMethodCalls());
		$this->assertEquals('_prePersist', $otto2->getMethodCalls()[0]);
		$this->assertEquals('_postPersist', $otto2->getMethodCalls()[1]);

		// test postLoad

		$tx = $tm->createTransaction();
		$tem = $this->emPool->getEntityManagerFactory()->getTransactional();

		$oto1 = $tem->find(ObservableTestObj::class, 1);
		$otto1 = $oto1->getObservableTargetTestObjs()->offsetGet(0);
		$otto2 = $oto1->getObservableTargetTestObj();

		$this->assertCount(1, $oto1->getMethodCalls());
		$this->assertEquals('_postLoad', $oto1->getMethodCalls()[0]);

		$this->assertCount(1, $otto1->getMethodCalls());
		$this->assertEquals('_postLoad', $otto1->getMethodCalls()[0]);

		$this->assertCount(1, $otto2->getMethodCalls());
		$this->assertEquals('_postLoad', $otto2->getMethodCalls()[0]);

		$this->assertCount(6, $this->ottoEntityListenerMock->getMethodCalls());
		$this->assertEquals('_postLoad', $this->ottoEntityListenerMock->getMethodCalls()[4]);
		$this->assertEquals('_postLoad', $this->ottoEntityListenerMock->getMethodCalls()[5]);

		$tx->commit();
	}


}