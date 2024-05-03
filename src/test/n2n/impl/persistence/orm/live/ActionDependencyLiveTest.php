<?php

namespace n2n\impl\persistence\orm\live;

use PHPUnit\Framework\TestCase;
use n2n\impl\persistence\orm\test\GeneralTestEnv;
use n2n\impl\persistence\orm\live\mock\JoinColumnSourceMock;
use n2n\impl\persistence\orm\live\mock\JoinColumnTargetMock;
use n2n\persistence\ext\EmPool;
use n2n\persistence\ext\PdoPool;
use n2n\core\container\TransactionManager;
use n2n\spec\dbo\meta\data\impl\QueryColumn;
use n2n\spec\dbo\meta\data\impl\QueryPlaceMarker;
use n2n\spec\dbo\meta\data\impl\QueryTable;

class ActionDependencyLiveTest extends TestCase {

	private EmPool $emPool;
	private PdoPool $pdoPool;
	private TransactionManager $tm;

	function setUp(): void {
		$this->emPool = GeneralTestEnv::setUpEmPool([JoinColumnSourceMock::class, JoinColumnTargetMock::class]);
		$this->pdoPool = $this->emPool->getPdoPool();
		$this->tm = $this->pdoPool->getTransactionManager();

		$metaData = $this->pdoPool->getPdo()->getMetaData();
		$database = $metaData->getDatabase();
		$metaEntityFactory = $database->createMetaEntityFactory();

		$table = $metaEntityFactory->createTable('join_column_source_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);
		$columnFactory->createIntegerColumn('target_mock_id', 32);

		$table = $metaEntityFactory->createTable('join_column_target_mock');
		$columnFactory = $table->createColumnFactory();
		$columnFactory->createIntegerColumn('id', 32);

		$metaData->getMetaManager()->flush();

		$stmtBuilder = $metaData->createInsertStatementBuilder();
		$stmtBuilder->setTable('join_column_source_mock');
		$stmtBuilder->addColumn(new QueryColumn('id'), new QueryPlaceMarker('id'));
		$stmtBuilder->addColumn(new QueryColumn('target_mock_id'), new QueryPlaceMarker('targetMockId'));
		$stmt = $this->pdoPool->getPdo()->prepare($stmtBuilder->toSqlString());
		$stmt->execute([':id' => 1, ':targetMockId' => 2]);

		$stmtBuilder = $metaData->createInsertStatementBuilder();
		$stmtBuilder->setTable('join_column_target_mock');
		$stmtBuilder->addColumn(new QueryColumn('id'), new QueryPlaceMarker('id'));
		$stmt = $this->pdoPool->getPdo()->prepare($stmtBuilder->toSqlString());
		$stmt->execute([':id' => 2]);
	}


	function testAsdf() {
		$tx = $this->tm->createTransaction(true);
		$tem = $this->emPool->getEntityManagerFactory()->getTransactional();

		$sourceMock = $tem->find(JoinColumnSourceMock::class, 1);
		$this->assertNotNull($sourceMock->getTargetMock());
		$this->assertEquals(2, $sourceMock->getTargetMock()->getId());

		$tx->commit();
	}

	function testAsdf2() {
		$this->assertCount(0, $this->emPool->getEntityModelManager()->getInitializedEntityModels());

		$tx = $this->tm->createTransaction();
		$tem = $this->emPool->getEntityManagerFactory()->getTransactional();

		$targetMock = $tem->find(JoinColumnTargetMock::class, 2);
		$this->assertNotNull($targetMock);

		$this->assertCount(1, $this->emPool->getEntityModelManager()->getInitializedEntityModels());
		$tem->remove($targetMock);

		$tx->commit();


//		$stmtBuilder = $this->pdoPool->getPdo()->getMetaData()->createSelectStatementBuilder();
//		$stmtBuilder->addFrom(new QueryTable('join_column_source_mock'));
//		$stmt = $this->pdoPool->getPdo()->prepare($stmtBuilder->toSqlString());
//		$stmt->execute();
//
//		var_dump($stmt->fetchAll());

		$this->assertCount(2, $this->emPool->getEntityModelManager()->getInitializedEntityModels());


		$tx = $this->tm->createTransaction(true);
		$tem = $this->emPool->getEntityManagerFactory()->getTransactional();

		$sourceMock = $tem->find(JoinColumnSourceMock::class, 1);
		$this->assertNull($sourceMock->getTargetMock());


		$tx->commit();


		$this->assertCount(2, $this->emPool->getEntityModelManager()->getInitializedEntityModels());

	}
}