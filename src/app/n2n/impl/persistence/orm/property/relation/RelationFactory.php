<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\persistence\orm\property\relation;

use n2n\persistence\orm\property\ClassSetup;
use n2n\impl\persistence\orm\property\RelationEntityProperty;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\OrmConfigurationException;
use n2n\persistence\orm\model\UnknownEntityPropertyException;
use n2n\impl\persistence\orm\property\relation\util\OrderDirective;
use n2n\persistence\orm\property\QueryItemRepresentableEntityProperty;
use n2n\persistence\orm\property\EntityProperty;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\FetchType;
use n2n\util\type\TypeUtils;
use n2n\persistence\orm\attribute\JoinColumn;
use n2n\persistence\orm\attribute\JoinTable;
use n2n\persistence\orm\attribute\OrderBy;
use n2n\persistence\orm\attribute\AssociationOverrides;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\OneToOne;
use n2n\reflection\attribute\Attribute;
use n2n\util\ex\IllegalStateException;
use n2n\reflection\attribute\PropertyAttribute;
use n2n\util\ex\err\ConfigurationError;
use n2n\persistence\orm\attribute\OrmRelationAttribute;

class RelationFactory {

	private ?Attribute $joinColumnAttribute = null;
	private ?Attribute $joinTableAttribute = null;
	private ?Attribute $orderByAttribute = null;

	public function __construct(private ClassSetup $classSetup, private RelationEntityProperty $relationProperty,
			private PropertyAttribute $relationAttribute) {


		$attributeSet = $classSetup->getAttributeSet();

		$this->determineAssociations($classSetup, array($relationProperty->getName()));

		if ($this->joinColumnAttribute === null && $this->joinTableAttribute === null) {
			$this->joinColumnAttribute = $attributeSet->getPropertyAttribute($relationProperty->getName(), JoinColumn::class);

			$this->joinTableAttribute = $attributeSet->getPropertyAttribute($relationProperty->getName(), JoinTable::class);
		}

		if ($this->joinColumnAttribute !== null && $this->joinTableAttribute !== null) {
			throw $classSetup->createException('Conflicting attributes: JoinColumn and JoinTable'
					. ' defined for entity property' . $this->classSetup->getClass()->getName()
					. '::$' . $relationProperty->getName() . '.',
					null, array($this->joinColumnAttribute, $this->joinTableAttribute));
		}

		$this->orderByAttribute = $attributeSet->getPropertyAttribute($relationProperty->getName(), OrderBy::class);
	}

	private function determineAssociations(ClassSetup $classSetup, array $propertyNames) {
		$parentPropertyName = $classSetup->getParentPropertyName();

		$parentClassSetup = $classSetup->getParentClassSetup();
		if ($parentClassSetup === null) return;

		$newPropertyNames = $propertyNames;
		if ($parentPropertyName !== null) {
			$newPropertyNames[] = $parentPropertyName;
		}

		$this->determineAssociations($parentClassSetup, $newPropertyNames);

		if ($this->joinColumnAttribute !== null && $this->joinTableAttribute !== null) {
			return;
		}

		$parentAttributeSet = $parentClassSetup->getAttributeSet();

		$attrAssociationOverrides = null;
		if (null !== $parentPropertyName) {
			$attrAssociationOverrides = $parentAttributeSet->getPropertyAttribute($parentPropertyName, AssociationOverrides::class);
		} else {
			$attrAssociationOverrides = $parentAttributeSet->getClassAttribute(AssociationOverrides::class);
		}

		if ($attrAssociationOverrides === null) return;

		$associationOverrides = $attrAssociationOverrides->getInstance();
		ArgUtils::assertTrue($associationOverrides instanceof AssociationOverrides);

		$associationPropertyName = implode(self::PROPERTY_NAME_SEPARATOR, $propertyNames);

		$attrJoinColumn = $associationOverrides->getJoinColumn();
		if ($this->joinColumnAttribute === null && isset($attrJoinColumn[$associationPropertyName])) {
			$this->joinColumnAttribute = $attrJoinColumn[$associationPropertyName];
		}

		$attrJoinTables = $associationOverrides->getJoinTables();
		if ($this->joinTableAttribute === null && isset($attrJoinTables[$associationPropertyName])) {
			$this->joinTableAttribute = $attrJoinTables[$associationPropertyName];
		}
	}

	public function createMappedOneToOneRelation($mappedBy, EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$targetEntityProperty = $this->determineTargetEntityProperty($mappedBy, $targetEntityModel);

		if ($targetEntityProperty->getType() != RelationEntityProperty::TYPE_ONE_TO_ONE) {
			throw $this->createAssociationException('one-to-one', 'one-to-one');
		}

		if (!$targetEntityProperty->isMaster()) {
			throw $this->createMappedToNonMasterException($targetEntityProperty);
		}

		$this->rejectJoinAttrs();

		$relation = new PropertyMappedOneToOneRelation($this->relationProperty, $targetEntityModel,
				$targetEntityProperty);
		$this->completeRelation($relation);
		return $relation;
	}

	private function completeRelation(Relation $relation) {
		$attrInstance = $this->relationAttribute->getInstance();
		$relation->setCascadeType($attrInstance->getCascade());
		$relation->setFetchType($attrInstance->getFetch());

		if ($attrInstance instanceof OneToMany || $attrInstance instanceof OneToOne) {
			$relation->setOrphanRemoval($attrInstance->isOrphanRemoval());
		}
	}

	public function createMappedOneToManyRelation($mappedBy, EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$targetEntityProperty = $this->determineTargetEntityProperty($mappedBy, $targetEntityModel);

		if ($targetEntityProperty->getType() != RelationEntityProperty::TYPE_MANY_TO_ONE) {
			throw $this->createAssociationException('one-to-many', 'many-to-one');
		}

		if (!$targetEntityProperty->isMaster()) {
			throw $this->createMappedToNonMasterException($targetEntityProperty);
		}

		$this->rejectJoinAttrs();

		$relation = new PropertyMappedToManyRelation($this->relationProperty, $targetEntityModel, $targetEntityProperty);
		$this->completeRelation($relation);
		$relation->setOrderDirectives($this->determineOrderDirectives($targetEntityModel));
		return $relation;
	}

	public function createMappedManyToManyRelation($mappedBy, EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager, true);
		$targetEntityProperty = $this->determineTargetEntityProperty($mappedBy, $targetEntityModel);

		if ($targetEntityProperty->getType() != RelationEntityProperty::TYPE_MANY_TO_MANY) {
			throw $this->createAssociationException('many-to-many', 'many-to-many');
		}

		if (!$targetEntityProperty->isMaster()) {
			throw $this->createMappedToNonMasterException($targetEntityProperty);
		}

		$this->rejectJoinAttrs();

		$relation = new PropertyMappedToManyRelation($this->relationProperty, $targetEntityModel,
				$targetEntityProperty);
		$relation->setOrderDirectives($this->determineOrderDirectives($targetEntityModel));
		$this->completeRelation($relation);
		return $relation;
	}

	private function rejectJoinAttrs() {
		if ($this->joinColumnAttribute !== null) {
			throw $this->classSetup->createException('Join column annotated to mapped property:'
					. $this->relationProperty->toPropertyString(),
					null, array($this->joinColumnAttribute));
		}

		if ($this->joinTableAttribute !== null) {
			throw $this->classSetup->createException('Join table annotated to mapped property:'
					. $this->relationProperty->toPropertyString(),
					null, array($this->joinTableAttribute));
		}
	}

	private function createAssociationException($typeName, $targetTypeName) {
		throw $this->classSetup->createException('Illegal attempt to associate ' . $typeName . ' '
				. $this->relationProperty->toPropertyString() . ' with non-' . $targetTypeName
				. ' property.', null, array($this->relationAttribute));
	}

	private function createMappedToNonMasterException(EntityProperty $targetEntityProperty) {
		throw $this->classSetup->createException('Illegal attempt to associate mapped relation property '
				. $this->relationProperty->toPropertyString() . ' with non-master property '
				. $targetEntityProperty->toPropertyString() . '.',
				null, array($this->relationAttribute));
	}

	public function createMasterToOneRelation(EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager);
		$namingStrategy = $this->classSetup->getNamingStrategy();

		if (null !== $this->joinTableAttribute) {
			$entityModel = $this->relationProperty->getEntityModel();
			$class = $entityModel->getClass();

			$relation = new JoinTableToOneRelation($this->relationProperty, $targetEntityModel);
			$relation->setJoinTableName($namingStrategy->buildJunctionTableName($entityModel->getTableName(),
					$this->relationProperty->getName(), $this->joinTableAttribute->getName()));
			$relation->setJoinColumnName($namingStrategy->buildJunctionJoinColumnName($class, $entityModel->getIdDef()->getPropertyName(),
					$this->joinTableAttribute->getJoinColumnName()));
			$relation->setInverseJoinColumnName($namingStrategy->buildJunctionJoinColumnName($targetEntityModel->getClass(),
					$targetEntityModel->getIdDef()->getPropertyName(),
					$this->joinTableAttribute->getInverseJoinColumnName()));
			$this->completeRelation($relation);
			return $relation;
		}

		$joinColumnName = null;
		if (null !== $this->joinColumnAttribute) {
			$joinColumnName = $this->joinColumnAttribute->getName();
		}

		$joinColumnName = $namingStrategy->buildJoinColumnName($this->relationProperty->getName(),
				$targetEntityModel->getIdDef()->getPropertyName(), $joinColumnName);

		$relation = new JoinColumnToOneRelation($this->relationProperty, $targetEntityModel);
		$relation->setJoinColumnName($this->classSetup->requestColumn($this->relationProperty->getName(),
				$joinColumnName, array($this->joinColumnAttribute)));

		$this->completeRelation($relation);
		return $relation;
	}

	const JOIN_MODE_COLUMN = 'joinColumn';
	const JOIN_MODE_TABLE = 'joinTable';

	public static function detectJoinMode($type, bool $attrJoinColumnAvailable,
			bool $attrJoinTableAvailable): string {

		switch ($type) {
			case RelationEntityProperty::TYPE_MANY_TO_ONE:
			case RelationEntityProperty::TYPE_ONE_TO_ONE:
				if ($attrJoinTableAvailable) return self::JOIN_MODE_TABLE;
				return self::JOIN_MODE_COLUMN;
			case RelationEntityProperty::TYPE_ONE_TO_MANY:
			case RelationEntityProperty::TYPE_MANY_TO_MANY:
				if ($attrJoinColumnAvailable) return self::JOIN_MODE_COLUMN;
				return self::JOIN_MODE_TABLE;
			default:
				throw new \InvalidArgumentException();
		}
	}

	public function createMasterToManyRelation(EntityModelManager $entityModelManager) {
		$targetEntityModel = $this->determineTargetEntityModel($entityModelManager);

		$namingStrategy = $this->classSetup->getNamingStrategy();

		$orderDirectives = $this->determineOrderDirectives($targetEntityModel);

		if (null !== $this->joinColumnAttribute) {
			if ($this->relationProperty->getType() != RelationEntityProperty::TYPE_ONE_TO_MANY) {
				throw $this->classSetup->createException('Invalid attribute for ' . $this->relationProperty->getType()
						. ' property', null, array($this->joinColumnAttribute));
			}

			$joinColumnName = $this->joinColumnAttribute->getName();
			if ($joinColumnName === null) {
				$namingStrategy->buildJunctionJoinColumnName($targetEntityModel->getClass(),
						$targetEntityModel->getIdDef()->getPropertyName(), $joinColumnName);
			}

			$relation = new InverseJoinColumnOneToManyRelation($this->relationProperty, $targetEntityModel);
			$relation->setInverseJoinColumnName($joinColumnName);
			$relation->setOrderDirectives($orderDirectives);
			$this->completeRelation($relation);
			return $relation;
		}

		$joinTableName = null;
		$joinColumnName = null;
		$inverseJoinColumnName = null;
		if (null !== $this->joinTableAttribute) {
			$joinTable = $this->joinTableAttribute->getInstance();
			$joinTableName = $joinTable->getName();
			$joinColumnName = $joinTable->getJoinColumnName();
			$inverseJoinColumnName = $joinTable->getInverseJoinColumnName();
		}

		$entityModel = $this->relationProperty->getEntityModel();
		$class = $entityModel->getClass();

		$relation = new JoinTableToManyRelation($this->relationProperty, $targetEntityModel);
		$relation->setJoinTableName($namingStrategy->buildJunctionTableName($entityModel->getTableName(),
				$this->relationProperty->getName(), $joinTableName));
		$relation->setJoinColumnName($namingStrategy->buildJunctionJoinColumnName($class, $entityModel->getIdDef()->getPropertyName(),
				$joinColumnName));
		$relation->setInverseJoinColumnName($namingStrategy->buildJunctionJoinColumnName($targetEntityModel->getClass(),
				$targetEntityModel->getIdDef()->getPropertyName(), $inverseJoinColumnName));
		$relation->setOrderDirectives($orderDirectives);
		$this->completeRelation($relation);
		return $relation;
	}

	private function determineTargetEntityModel(EntityModelManager $entityModelManager) {
		$targetEntityModel = null;

		$relationAttrInstance = $this->relationAttribute?->getInstance();
		ArgUtils::valType($relationAttrInstance, OrmRelationAttribute::class);

		try {
			$targetClass = self::readTargetClass($this->relationAttribute, $relationAttrInstance->getTargetEntity());
			$targetEntityModel = $entityModelManager->getEntityModelByClass($targetClass);
		} catch (OrmConfigurationException $e) {
			throw $this->classSetup->createException($this->classSetup->buildPropertyString(
							$this->relationProperty->getName())
					. ' is annotated with invalid target entity.', $e,
					array($relationAttrInstance));
		}

		$type = $this->relationProperty->getType();
		if (($type == RelationEntityProperty::TYPE_MANY_TO_ONE || $type == RelationEntityProperty::TYPE_ONE_TO_ONE)
				&& $targetEntityModel->hasSubEntityModels()
				&& ($relationAttrInstance === null || $relationAttrInstance->getFetchType() !== FetchType::EAGER)) {
			throw $this->classSetup->createException('Lazy fetch disallowed for '
					. $this->classSetup->buildPropertyString($this->relationProperty->getName())
					. '. ' . $this->relationProperty->getType()
					. ' properties which refer to entities which are inherited by other entities must be eager'
					. ' fetched (FetchType::EAGER).',
					null, array($relationAttrInstance));
		}

		return $targetEntityModel;
	}

	const PROPERTY_NAME_SEPARATOR = '.';

	private function determineTargetEntityProperty($mappedBy, EntityModel $targetEntityModel) {
		$nextPropertyNames = explode(self::PROPERTY_NAME_SEPARATOR, $mappedBy);
		$targetEntityPropertyCollection = $targetEntityModel;

		try {
			$targetEntityProperty = $this->determineEntityProperty($mappedBy, $targetEntityModel);
			if ($targetEntityProperty instanceof RelationEntityProperty) return $targetEntityProperty;

			throw $this->classSetup->createException('Illegal attempt to associate relation property '
					. $this->relationProperty->toPropertyString() . ' with non relation property.',
					null, array($this->relationAttribute));
		} catch (UnknownEntityPropertyException $e) {
			throw $this->classSetup->createException($this->classSetup->getClass()->getName() . '::$'
					. $this->relationProperty->getName() . ' is mapped by unknown entity property '
					. TypeUtils::prettyClassPropName($targetEntityModel->getClass(), $mappedBy)
					. '.', $e, array($this->relationAttribute));
		}
	}

	private function determineOrderDirectives(EntityModel $targetEntityModel) {
		if ($this->orderByAttribute === null) return array();

		$orderBy = $this->orderByAttribute->getInstance();
		IllegalStateException::assertTrue($orderBy instanceof OrderBy);

		$orderDirectives = array();
		foreach ($orderBy->getOrderDefs() as $propertyExpression => $direction) {
			try {
				$propertyNames = array();
				$targetEntityProperty = $this->determineEntityProperty($propertyExpression,
						$targetEntityModel, $propertyNames);

				if ($targetEntityProperty instanceof QueryItemRepresentableEntityProperty) {
					$orderDirectives[] = new OrderDirective($propertyNames, $direction);
					continue;
				}

				throw $this->classSetup->createException('Property '
						. $this->relationProperty->toPropertyString() . ' can not be used in order directives.',
						null, array($this->orderByAttribute));
			} catch (UnknownEntityPropertyException $e) {
				throw $this->classSetup->createException($this->classSetup->getClass()->getName() . '::$'
						. $this->relationProperty->getName() . ' is ordered by unknown entity property \''
						. $propertyExpression . '\'.', $e, array($this->orderByAttribute));
			}
		}
		return $orderDirectives;
	}

	private function determineEntityProperty($propertyExpression, EntityModel $entityModel, array &$propertyNames = array()) {
		$nextPropertyNames = explode(self::PROPERTY_NAME_SEPARATOR, $propertyExpression);
		$entityPropertyCollection = $entityModel;

		while (null !== ($propertyName = array_shift($nextPropertyNames))) {
			$propertyNames[] = $propertyName;
			$entityProperty = $entityPropertyCollection->getEntityPropertyByName($propertyName);
			if (empty($nextPropertyNames)) {
				return $entityProperty;
			}

			if ($entityProperty->hasEmbeddedEntityPropertyCollection()) {
				$entityPropertyCollection = $entityProperty->getEmbeddedEntityPropertyCollection();
				continue;
			}

			throw new UnknownEntityPropertyException('Unresolvable entity property: '
					. $this->relationProperty->getEntityModel()->getClass()->getName() . '::$'
					. implode('::$', $propertyNames));
		}
	}

	public static function readTargetClass(PropertyAttribute $propertyAttribute, ?string $attributeTargetClassName) {
		$targetEntityName = $attributeTargetClassName ?? (string) $propertyAttribute->getProperty()->getType();

		if ($targetEntityName === null) {
			throw new ConfigurationError('TargetEntity not declared for: ' . TypeUtils::prettyReflPropName($propertyAttribute->getProperty()),
					$propertyAttribute->getFile(), $propertyAttribute->getLine());
		}

		try {
			return new \ReflectionClass($targetEntityName);
		} catch (\ReflectionException $e) {
			throw new ConfigurationError('TargetEntity invalid: ' .
					TypeUtils::prettyReflPropName($propertyAttribute->getProperty()), $propertyAttribute->getFile(),
					$propertyAttribute->getLine(), null, null, $e);
		}
	}
}
