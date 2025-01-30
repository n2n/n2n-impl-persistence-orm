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

class ToManyEntityPropertyTest extends TestCase {

	private DbTestPdoUtil $pdoUtil;
	private EmPool $emPool;
	private PdoPool $pdoPool;
	private LifecycleListener $lifecycleListener;


	public function setUp(): void {
//		$this->emm = new EntityModelManager([ScalarValueObjectEntityMock::class],
//				new EntityModelFactory([CommonEntityPropertyProvider::class]));

		$this->emPool = GeneralTestEnv::setUpEmPool([ToManyEntityMock::class, SimpleTargetMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('to_many_entity_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);

		$table = $metaEntityFactory->createTable('to_many_entity_mock_join_table_targets');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('to_many_entity_mock_id', 32);
		$columnFactory->createIntegerColumn('simple_target_mock_id', 32);

		$table = $metaEntityFactory->createTable('simple_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createStringColumn('holeradio', 255);
		$columnFactory->createIntegerColumn('inverse_join_column_id', 32);

		$metaData->getMetaManager()->flush();

		$this->pdoUtil = new DbTestPdoUtil($this->pdoPool->getPdo());
	}
	private function tem(): EntityManager {
		return $this->emPool->getEntityManagerFactory()->getTransactional();
	}

	function testLifecycle() {
		$tm = $this->pdoPool->getTransactionManager();
		$this->lifecycleListener = GeneralTestEnv::getLifecycleListener();

		$tmem = new ToManyEntityMock();
		$tmem->id = 1;
		$target = new SimpleTargetMock();
		$target->id = 1;
		$target->holeradio = 'table';
		$tmem->joinTableTargets->append($target);
		$target = new SimpleTargetMock();
		$target->id = 2;
		$target->holeradio = 'inverse join';
		$tmem->inverseJoinColumnTargets->append($target);

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEmpty(0, $this->lifecycleListener->getNum());

		$tx = $tm->createTransaction();
		$this->tem()->persist($tmem);

		$this->assertCount(0, $this->lifecycleListener->getClassNames());
		$this->assertEquals(0, $this->lifecycleListener->getNum());
		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->prePersistNums[ToManyEntityMock::class]);
		$this->assertEquals(2, $this->lifecycleListener->prePersistNums[SimpleTargetMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postPersistNums[ToManyEntityMock::class]);
		$this->assertEquals(2, $this->lifecycleListener->postPersistNums[SimpleTargetMock::class]);
		$this->assertCount(2, $this->lifecycleListener->events[ToManyEntityMock::class]);
		$this->assertCount(4, $this->lifecycleListener->events[SimpleTargetMock::class]);

		// VOID UPDATE

		$tx = $tm->createTransaction();

		$tmem = $this->tem()->find(ToManyEntityMock::class, $tmem->id);
		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(6, $this->lifecycleListener->getNum());
		$this->assertCount(2, $this->lifecycleListener->events[ToManyEntityMock::class]);
		$this->assertCount(4, $this->lifecycleListener->events[SimpleTargetMock::class]);


		// UPDATE TABLE

		$tx = $tm->createTransaction();

		$tmem = $this->tem()->find(ToManyEntityMock::class, $tmem->id);
		$target = new SimpleTargetMock();
		$target->id = 3;
		$target->holeradio = 'table 2';
		$tmem->joinTableTargets->append($target);

		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(10, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preUpdateNums[ToManyEntityMock::class]);
		$this->assertEquals(1, $this->lifecycleListener->postUpdateNums[ToManyEntityMock::class]);
		$events = $this->lifecycleListener->events[ToManyEntityMock::class];
		$this->assertCount(4, $events);

		$this->assertTrue($events[2]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[2]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[2]->containsChangesForAnyBut('joinTableTargets'));
		$this->assertTrue($events[2]->containsChangesForAnyBut('inverseJoinColumnTargets'));
		$this->assertTrue($events[3]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[3]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[3]->containsChangesForAnyBut('joinTableTargets'));
		$this->assertTrue($events[3]->containsChangesForAnyBut('inverseJoinColumnTargets'));


		// UPDATE INVERSE JOIN COLUMN

		$tx = $tm->createTransaction();

		$tmem = $this->tem()->find(ToManyEntityMock::class, $tmem->id);
		$target = new SimpleTargetMock();
		$target->id = 4;
		$target->holeradio = 'inverse join 2';
		$tmem->inverseJoinColumnTargets->append($target);

		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(14, $this->lifecycleListener->getNum());
		$this->assertEquals(2, $this->lifecycleListener->preUpdateNums[ToManyEntityMock::class]);
		$this->assertEquals(2, $this->lifecycleListener->postUpdateNums[ToManyEntityMock::class]);
		$events = $this->lifecycleListener->events[ToManyEntityMock::class];
		$this->assertCount(6, $events);

		$this->assertTrue($events[4]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[4]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[4]->containsChangesForAnyBut('inverseJoinColumnTargets'));
		$this->assertTrue($events[4]->containsChangesForAnyBut('joinTableTargets'));
		$this->assertTrue($events[5]->containsChangesFor('inverseJoinColumnTargets'));
		$this->assertFalse($events[5]->containsChangesFor('joinTableTargets'));
		$this->assertFalse($events[5]->containsChangesForAnyBut('inverseJoinColumnTargets'));
		$this->assertTrue($events[5]->containsChangesForAnyBut('joinTableTargets'));

		// REMOVE

		$tx = $tm->createTransaction();

		$tmem = $this->tem()->find(ToManyEntityMock::class, $tmem->id);

		$this->tem()->remove($tmem);

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(19, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->preRemoveNums[ToManyEntityMock::class]);

		$tx->commit();

		$this->assertCount(2, $this->lifecycleListener->getClassNames());
		$this->assertEquals(24, $this->lifecycleListener->getNum());
		$this->assertEquals(1, $this->lifecycleListener->postRemoveNums[ToManyEntityMock::class]);
	}
}