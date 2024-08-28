<?php

namespace n2n\impl\persistence\orm\property\embedded;

use n2n\persistence\ext\PdoPool;
use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\live\mock\EmbeddedContainerMock;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use n2n\impl\persistence\orm\live\mock\EmbeddableMock;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\live\mock\OverrideEmbeddedContainerMock;
use n2n\persistence\ext\EmPool;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\persistence\orm\LifecycleEvent;

class EmbeddedLiveTest extends TestCase {
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;

	function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([EmbeddedContainerMock::class, SimpleTargetMock::class, OverrideEmbeddedContainerMock::class]);
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('embedded_container_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('holeradio_name', 255);
		$columnFactory->createIntegerColumn('holeradio_very_simple_target_mock_id', 32);

		$table = $metaEntityFactory->createTable('override_embedded_container_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('override_name', 255);
		$columnFactory->createIntegerColumn('override_very_simple_target_mock_id', 32);

		$table = $metaEntityFactory->createTable('simple_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('holeradio_embeddable_mock_id', 32);
		$columnFactory->createStringColumn('holeradio', 255);

		$table = $metaEntityFactory->createTable('holeradio_embedded_container_mock_not_simple_target_mocks');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('holeradio_embedded_container_mock_id', 32);
		$columnFactory->createIntegerColumn('holeradio_simple_target_mock_id', 32);

		$table = $metaEntityFactory->createTable('holeradio_embedded_container_mock_many_simple_target_mocks');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('holeradio_embedded_container_mock_id', 32);
		$columnFactory->createIntegerColumn('holeradio_simple_target_mock_id', 32);

		$metaData->getMetaManager()->flush();
	}

	function testNotFound() {
		$em = $this->emPool->getEntityManagerFactory()->getExtended();

		$this->assertNull($em->find(EmbeddedContainerMock::class, 1));
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

	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLifecycle() {
		$tm = $this->pdoPool->getTransactionManager();

		$ecm = new EmbeddedContainerMock();
		$ecm->id = 1;
		$ecm->embeddableMock = new EmbeddableMock();
		$ecm->embeddableMock->name = 'huii';

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($ecm);

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(1, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->prePersistNums[EmbeddedContainerMock::class]);

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[EmbeddedContainerMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$ecm = $this->tem()->find(EmbeddedContainerMock::class, $ecm->id);
		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(2, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[EmbeddedContainerMock::class]);


		// UPDATE

		$tx = $tm->createTransaction();

		$ecm = $this->tem()->find(EmbeddedContainerMock::class, $ecm->id);
		$ecm->embeddableMock->name = 'holeradio';

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(4, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[EmbeddedContainerMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[EmbeddedContainerMock::class]);
		$events = $this->lifecycleListener->events[EmbeddedContainerMock::class];
		$this->assertCount(4, $events);

		$this->assertEquals(LifecycleEvent::PRE_UPDATE, $events[2]->getType());
		$this->assertTrue($events[2]->containsChangesFor('embeddableMock'));
		$this->assertTrue($events[2]->containsChangesFor('embeddableMock.name'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('embeddableMock'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('embeddableMock.name'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('embeddableMock.simpleTargetMocks'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('id'));
		$this->assertEquals(LifecycleEvent::POST_UPDATE, $events[3]->getType());
		$this->assertTrue($events[3]->containsChangesFor('embeddableMock'));
		$this->assertTrue($events[3]->containsChangesFor('embeddableMock.name'));
		$this->assertFalse($events[3]->containsChangesForAnyBut('embeddableMock'));
		$this->assertFalse($events[3]->containsChangesForAnyBut('embeddableMock.name'));
		$this->assertTrue($events[3]->containsChangesForAnyBut('id'));
		$this->assertTrue($events[3]->containsChangesForAnyBut('embeddableMock.simpleTargetMocks'));

		// REMOVE

		$tx = $tm->createTransaction();

		$ecm = $this->tem()->find(EmbeddedContainerMock::class, $ecm->id);

		$this->tem()->remove($ecm);

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(5, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[EmbeddedContainerMock::class]);

		$tx->commit();

		$this->assertCount(1, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[EmbeddedContainerMock::class]);
	}

	private function countEntityObjs(EntityManager $em, string $entityClass): int {
		return $em->createCriteria()->select('COUNT(1)')->from($entityClass, 'e')
				->toQuery()->fetchSingle();
	}
}
