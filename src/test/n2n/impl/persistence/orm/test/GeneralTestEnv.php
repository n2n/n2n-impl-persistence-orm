<?php
namespace n2n\impl\persistence\orm\test;

use n2n\persistence\ext\PdoPool;
use n2n\core\config\PersistenceUnitConfig;
use n2n\impl\persistence\meta\sqlite\SqliteDialect;
use n2n\core\container\TransactionManager;
use n2n\persistence\ext\EmPool;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\impl\persistence\orm\property\CommonEntityPropertyProvider;
use n2n\util\magic\impl\SimpleMagicContext;
use n2n\impl\persistence\orm\live\mock\LifecycleListener;

class GeneralTestEnv {

	private static LifecycleListener $lifecycleListener;

	static function setUpEmPool(array $registeredClassNames, array $magicContextObjs = []): EmPool {
		$pdoPool = new PdoPool(
				[PdoPool::DEFAULT_DS_NAME => new PersistenceUnitConfig('default', 'sqlite::memory:', '', '',
						PersistenceUnitConfig::TIL_SERIALIZABLE, SqliteDialect::class,
						false, null)],
				new TransactionManager(), null, null);

		return new EmPool($pdoPool,
				new EntityModelManager(
						$registeredClassNames,
						new EntityModelFactory([CommonEntityPropertyProvider::class])),
				new SimpleMagicContext([...$magicContextObjs, LifecycleListener::class => self::$lifecycleListener = new LifecycleListener()]));
	}

	static function getLifecycleListener(): LifecycleListener {
		return self::$lifecycleListener;
	}
}