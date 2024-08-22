<?php

namespace n2n\impl\persistence\orm\property\relation;

use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\test\DbTestPdoUtil;
use n2n\impl\persistence\orm\property\relation\mock\ToManyEntityMock;
use n2n\impl\persistence\orm\live\mock\SimpleTargetMock;
use PHPUnit\Framework\TestCase;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\impl\persistence\orm\live\mock\EmbeddedContainerMock;
use n2n\impl\persistence\orm\live\mock\EmbeddableMock;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;
use n2n\persistence\orm\EntityManager;
use n2n\impl\persistence\orm\property\relation\mock\ToOneEntityMock;

class ToOneEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;


	public function setUp(): void {
//		$this->emm = new EntityModelManager([ScalarValueObjectEntityMock::class],
//				new EntityModelFactory([CommonEntityPropertyProvider::class]));

		$this->emPool = GeneralTestEnv::setUpEmPool([ToOneEntityMock::class, SimpleTargetMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('to_one_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('join_column_target_id', 32);

		$table = $metaEntityFactory->createTable('join_table');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('to_one_entity_mock_id', 32);
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

	function testLifecycle() {
		$this->markTestIncomplete('JoinTableToOneRelation must be fixed first');

		$tm = $this->pdoPool->getTransactionManager();
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();

		$toem = new ToOneEntityMock();
		$toem->id = 1;
		$target = new SimpleTargetMock();
		$target->id = 1;
		$target->holeradio = 'table';
		$toem->joinTableTarget = $target;
		$target = new SimpleTargetMock();
		$target->id = 2;
		$target->holeradio = 'inverse join';
		$toem->joinColumnTarget = $target;

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($toem);

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(3, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->prePersistNums[ToOneEntityMock::class]);
		$this->assertEquals(2, $this->lifecycleListener->prePersistNums[SimpleTargetMock::class]);
		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[ToOneEntityMock::class]);
		$this->assertEquals(2, $this->lifecycleListener->postPersistNums[SimpleTargetMock::class]);
		$this->assertCount(2, $this->lifecycleListener->events[ToOneEntityMock::class]);
		$this->assertCount(4, $this->lifecycleListener->events[SimpleTargetMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$toem = $this->tem()->find(ToOneEntityMock::class, $toem->id);
		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[ToOneEntityMock::class]);
		$this->assertCount(4, $this->lifecycleListener->events[SimpleTargetMock::class]);


		// UPDATE TABLE

		$tx = $tm->createTransaction();

		$toem = $this->tem()->find(ToOneEntityMock::class, $toem->id);
		$target = new SimpleTargetMock();
		$target->id = 3;
		$target->holeradio = 'table 2';
		$toem->joinTableTarget->append($target);

		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(10, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[ToOneEntityMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[ToOneEntityMock::class]);
		$events = $this->lifecycleListener->events[ToOneEntityMock::class];
		$this->assertCount(4, $events);

		$this->assertTrue($events[2]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[2]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('joinTableTargets'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('inverseJoinColumnTargets'));
		$this->assertTrue($events[3]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[3]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[3]->containsChangesForAnyBut('joinTableTargets'));
		$this->assertFalse($events[3]->containsChangesForAnyBut('inverseJoinColumnTargets'));


		// UPDATE INVERSE JOIN COLUMN

		$tx = $tm->createTransaction();

		$toem = $this->tem()->find(ToOneEntityMock::class, $toem->id);
		$target = new SimpleTargetMock();
		$target->id = 4;
		$target->holeradio = 'inverse join 2';
		$toem->inverseJoinColumnTargets->append($target);

		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(14, $this->lifecycleListener->getNum());
		$this->assertEquals(2, $this->lifecycleListener->preUpdateNums[ToOneEntityMock::class]);
		$this->assertEquals(2, $this->lifecycleListener->postUpdateNums[ToOneEntityMock::class]);
		$events = $this->lifecycleListener->events[ToOneEntityMock::class];
		$this->assertCount(6, $events);

		$this->assertTrue($events[4]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[4]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[4]->containsChangesForAnyBut('inverseJoinColumnTargets'));
		$this->assertFalse($events[4]->containsChangesForAnyBut('joinTableTargets'));
		$this->assertTrue($events[5]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[5]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[5]->containsChangesForAnyBut('inverseJoinColumnTargets'));
		$this->assertFalse($events[5]->containsChangesForAnyBut('joinTableTargets'));

		// REMOVE

		$tx = $tm->createTransaction();

		$toem = $this->tem()->find(ToOneEntityMock::class, $toem->id);

		$this->tem()->remove($toem);

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(19, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[ToOneEntityMock::class]);

		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(24, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[ToOneEntityMock::class]);
	}
}