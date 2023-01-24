<?php

namespace n2n\impl\persistence\orm\property\relation;

use n2n\persistence\orm\model\EntityModel;
use n2n\util\type\ArgUtils;
use n2n\util\ex\IllegalStateException;

class LazyRelation {

	function __construct(private EntityModel $targetEntityModel, private \Closure $relationCallback) {

	}

	function getTargetEntityModel(): EntityModel {
		return $this->targetEntityModel;
	}

	function obtainRelation(): Relation {
		IllegalStateException::assertTrue(isset($this->relationCallback));

		$callback = $this->relationCallback;
		$relation = $callback();
		ArgUtils::valTypeReturn($relation, Relation::class, null, $callback);
		unset($this->relationCallback);
		return $relation;
	}
}