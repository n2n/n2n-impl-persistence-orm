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
namespace n2n\impl\persistence\orm\property;

use n2n\persistence\orm\property\EntityPropertyProvider;
use n2n\reflection\property\AccessProxy;
use n2n\persistence\orm\property\ClassSetup;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\impl\persistence\orm\property\relation\RelationFactory;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\persistence\orm\model\NamingStrategy;
use n2n\util\type\ArgUtils;
use n2n\io\orm\FileEntityProperty;
use n2n\io\orm\ManagedFileEntityProperty;
use n2n\io\managed\impl\SimpleFileLocator;
use n2n\util\io\IoUtils;
use n2n\util\uri\Url;
use n2n\util\type\TypeName;
use n2n\reflection\ReflectionContext;
use n2n\persistence\orm\attribute\DateTime;
use n2n\persistence\orm\attribute\N2nLocale;
use n2n\persistence\orm\attribute\ManagedFile;
use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\ManyToMany;
use n2n\persistence\orm\attribute\Embedded;
use n2n\reflection\attribute\PropertyAttribute;
use n2n\reflection\attribute\Attribute;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\util\type\TypeConstraints;
use n2n\persistence\orm\attribute\AttributeOverrides;
use n2n\util\type\TypeUtils;
use n2n\spec\valobj\scalar\StringValueObject;

class CommonEntityPropertyProvider implements EntityPropertyProvider {
	const PROP_FILE_NAME_SUFFIX = '.originalName';
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityPropertyProvider::setupPropertyIfSuitable()
	 */
	public function setupPropertyIfSuitable(AccessProxy $propertyAccessProxy, ClassSetup $classSetup): void {

		$attributeSet = ReflectionContext::getAttributeSet($classSetup->getClass());
		$propertyName = $propertyAccessProxy->getPropertyName();

		if (null !== ($dateTimeAttribute = $attributeSet->getPropertyAttribute($propertyName, DateTime::class))) {
			$classSetup->provideEntityProperty(new DateTimeEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName)), array($dateTimeAttribute));
			return;
		}
		
		if (null !== ($n2nLocaleAttribute = $attributeSet->getPropertyAttribute($propertyName, N2nLocale::class))) {
			$classSetup->provideEntityProperty(new N2nLocaleEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName)), array($n2nLocaleAttribute));
			return;
		}

		if (null !== ($urlAttribute = $attributeSet->getPropertyAttribute($propertyName,
						\n2n\persistence\orm\attribute\Url::class))) {
			$classSetup->provideEntityProperty(new UrlEntityProperty($propertyAccessProxy,
					$classSetup->requestColumn($propertyName)), array($urlAttribute));
			return;
		}
		
//		if (null !== ($attrFile = $attributeSet->getPropertyAttribute($propertyName, File::class))) {
//			$classSetup->provideEntityProperty(new FileEntityProperty($propertyAccessProxy,
//					$classSetup->requestColumn($propertyName),
//					$classSetup->requestColumn($propertyName . self::PROP_FILE_NAME_SUFFIX),
//					$attrFile->getInstance()->getOriginalNameColumnName()->getOriginalNameColumnName()),
//					array($attrFile->getInstance()));
//		}

		$managedFileAttribute = $attributeSet->getPropertyAttribute($propertyName, ManagedFile::class)?->getInstance();
		if (null !== $managedFileAttribute) {
			$manageFileEntityProperty = new ManagedFileEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName), $managedFileAttribute->getLookupId(),
					$managedFileAttribute->isCascadeDelete());
					
			if (null !== ($fileLocator = $managedFileAttribute->getFileLocator())) {
				$manageFileEntityProperty->setFileLocator($fileLocator);
			} else {
				$manageFileEntityProperty->setFileLocator(new SimpleFileLocator(
						mb_strtolower(IoUtils::stripSpecialChars($classSetup->getClass()->getShortName()))));
			}
			
			$classSetup->provideEntityProperty($manageFileEntityProperty, array($managedFileAttribute));
			return;
		}

		if ($this->checkForRelations($propertyAccessProxy, $classSetup)) {
			return;
		}
		
		if ($this->checkForEmbedded($propertyAccessProxy, $classSetup)) {
			return;
		}

		foreach ($this->determineTypeNames($propertyAccessProxy, $classSetup) as $typeName) {
			switch ($typeName) {
				case TypeName::BOOL:
					$classSetup->provideEntityProperty(new BoolEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case TypeName::INT:
					$classSetup->provideEntityProperty(new IntEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case TypeName::FLOAT:
					$classSetup->provideEntityProperty(new FloatEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case TypeName::STRING:
					$classSetup->provideEntityProperty(new StringEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case \DateTime::class:
					$classSetup->provideEntityProperty(new DateTimeEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case \n2n\l10n\N2nLocale::class:
					$classSetup->provideEntityProperty(new N2nLocaleEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case \n2n\io\managed\File::class:
					$classSetup->provideEntityProperty(new FileEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName), null));
					return;
				case Url::class:
					$classSetup->provideEntityProperty(new UrlEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
				case TypeName::PSEUDO_SCALAR:
					$classSetup->provideEntityProperty(new ScalarEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					return;
			}

			if (enum_exists($typeName)) {
				$classSetup->provideEntityProperty(new EnumEntityProperty($propertyAccessProxy,
						$classSetup->requestColumn($propertyName), new \ReflectionEnum($typeName)));
				return;
			}

			if (TypeUtils::isTypeA($typeName, StringValueObject::class)) {
				$classSetup->provideEntityProperty(new StringValueObjectEntityProperty($propertyAccessProxy,
						$classSetup->requestColumn($propertyName), new \ReflectionClass($typeName)));
				return;
			}
		}
	}
	
	private function checkForEmbedded(PropertyAccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {
		$propertyName = $propertyAccessProxy->getPropertyName();
		$attributeSet = $classSetup->getAttributeSet();
		$attrEmbedded = $attributeSet->getPropertyAttribute($propertyName, Embedded::class);
		if ($attrEmbedded === null) {
			return false;
		}

		ArgUtils::assertTrue($attrEmbedded instanceof PropertyAttribute);

		$embedded = $attrEmbedded->getInstance();
		ArgUtils::assertTrue($embedded instanceof Embedded);

		$targetClass = RelationFactory::readTargetClass($attrEmbedded, $embedded->getTargetEntity(), $classSetup);
		$embeddedEntityProperty = new EmbeddedEntityProperty($propertyAccessProxy, $targetClass);
				
		$classSetup->provideEntityProperty($embeddedEntityProperty);

		$setupProcess = $classSetup->getSetupProcess();
		$targetClassSetup = new ClassSetup($setupProcess, $targetClass,
				new EmbeddedNampingStrategy($classSetup->getNamingStrategy(), $embedded->getColumnPrefix(),
						$embedded->getColumnSuffix()),
				$classSetup, $propertyName);

		if (null !== ($attrAttributeOverridesAttr = $attributeSet
						->getPropertyAttribute($propertyName, AttributeOverrides::class))) {
			$targetClassSetup->addAttributeOverrides($attrAttributeOverridesAttr->getInstance());
		}

//		if (null !== ($attrAssociationOverridesAttr = $attributeSet
//						->getPropertyAttribute($propertyName, AssociationOverrides::class))) {
//			$targetClassSetup->addAttributeOverrides($attrAssociationOverridesAttr->getInstance());
//		}

		$setupProcess->getEntityPropertyAnalyzer()->analyzeClass($targetClassSetup);

		foreach ($targetClassSetup->getEntityProperties() as $property) {
			$embeddedEntityProperty->addEntityProperty($property);
		}
		
		return true;
	}
	
	private function checkForRelations(AccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {
		$propertyName = $propertyAccessProxy->getPropertyName();
		$attributeSet = $classSetup->getAttributeSet();

		$oneToOneAttribute = $attributeSet->getPropertyAttribute($propertyName, OneToOne::class);
		if (null !== $oneToOneAttribute) {
			$this->provideOneToOne($propertyAccessProxy, $oneToOneAttribute, $classSetup);
			return true;
		}

		$manyToOneAttribute = $attributeSet->getPropertyAttribute($propertyName, ManyToOne::class);
		if (null !== $manyToOneAttribute) {
			$this->provideManyToOne($propertyAccessProxy, $manyToOneAttribute, $classSetup);
			return true;
		}

		$oneToManyAttribute = $attributeSet->getPropertyAttribute($propertyName, OneToMany::class);
		if (null !== $oneToManyAttribute) {
			$this->provideOneToMany($propertyAccessProxy, $oneToManyAttribute, $classSetup);
			return true;
		}

		$manyToManyAttribute = $attributeSet->getPropertyAttribute($propertyName, ManyToMany::class);
		if (null !== $manyToManyAttribute) {
			$this->provideManyToMany($propertyAccessProxy, $manyToManyAttribute, $classSetup);
			return true;
		}

		return false;
	}
	
	private function provideOneToOne(AccessProxy $propertyAccessProxy, 
			PropertyAttribute $oneToOneAttribute, ClassSetup $classSetup) {
		$oneToOne = $oneToOneAttribute->getInstance();
		ArgUtils::assertTrue($oneToOne instanceof OneToOne);

		$toOneProperty = new ToOneEntityProperty($propertyAccessProxy,
				$oneToOne->getMappedBy() === null, RelationEntityProperty::TYPE_ONE_TO_ONE);
		$classSetup->provideEntityProperty($toOneProperty);

		$relationFactory = new RelationFactory($classSetup, $toOneProperty, $oneToOneAttribute);

		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toOneProperty, $oneToOne, $relationFactory) {
			if (null !== ($mappedBy = $oneToOne->getMappedBy())) {
				$toOneProperty->setLazyRelation($relationFactory->createMappedOneToOneRelation(
						$mappedBy, $entityModelManager));
			} else {
				$toOneProperty->setLazyRelation($relationFactory
						->createMasterToOneRelation($entityModelManager));
			}
		}, $toOneProperty->isMaster());
	}
	
	private function provideManyToOne(AccessProxy $propertyAccessProxy, 
			Attribute $manyToOneAttribute, ClassSetup $classSetup) {
		$toOneProperty = new ToOneEntityProperty($propertyAccessProxy, true, 
				RelationEntityProperty::TYPE_MANY_TO_ONE);
		$classSetup->provideEntityProperty($toOneProperty);
		
		$relationFactory = new RelationFactory($classSetup, $toOneProperty, $manyToOneAttribute);

		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toOneProperty, $relationFactory, $classSetup) {
			$toOneProperty->setLazyRelation($relationFactory->createMasterToOneRelation($entityModelManager));
		}, true);
	}
	
	private function provideOneToMany(AccessProxy $propertyAccessProxy,
			PropertyAttribute $oneToManyAttribute, ClassSetup $classSetup) {
		$oneToMany = $oneToManyAttribute->getInstance();
		$toManyProperty = new ToManyEntityProperty($propertyAccessProxy,
				$oneToMany->getMappedBy() === null, RelationEntityProperty::TYPE_ONE_TO_MANY);
		$classSetup->provideEntityProperty($toManyProperty);
		
		$relationFactory = new RelationFactory($classSetup, $toManyProperty, $oneToManyAttribute);

		if (!$toManyProperty->isMaster()) {
			$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
					use ($toManyProperty, $oneToMany, $relationFactory) {
						$entityModelManager->getEntityModelByClass($oneToMany->getTargetEntity());
			}, true);
		}
			
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toManyProperty, $oneToMany, $relationFactory) {
			if (null !== ($mappedBy = $oneToMany->getMappedBy())) {
				$toManyProperty->setLazyRelation($relationFactory->createMappedOneToManyRelation(
						$mappedBy, $entityModelManager));
			} else {
				$toManyProperty->setLazyRelation($relationFactory
						->createMasterToManyRelation($entityModelManager));
			}
		}, $toManyProperty->isMaster());
	}
	
	private function provideManyToMany(AccessProxy $propertyAccessProxy,
			PropertyAttribute $manyToManyAttribute, ClassSetup $classSetup) {
		$manyToMany = $manyToManyAttribute->getInstance();
		ArgUtils::assertTrue($manyToMany instanceof ManyToMany);

		$manyToManyProperty = new ToManyEntityProperty($propertyAccessProxy,
				$manyToMany->getMappedBy() === null, RelationEntityProperty::TYPE_MANY_TO_MANY);
		$classSetup->provideEntityProperty($manyToManyProperty);
			
		$relationFactory = new RelationFactory($classSetup, $manyToManyProperty, $manyToManyAttribute);
		
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($manyToManyProperty, $manyToMany, $relationFactory) {
			if (null !== ($mappedBy = $manyToMany->getMappedBy())) {
				$manyToManyProperty->setLazyRelation($relationFactory->createMappedManyToManyRelation(
						$mappedBy, $entityModelManager));
			} else {
				$manyToManyProperty->setLazyRelation($relationFactory->createMasterToManyRelation($entityModelManager));
			}
		}, $manyToManyProperty->isMaster());
	}

	private function determineTypeNames(PropertyAccessProxy $propertyAccessProxy, ClassSetup $classSetup): array {
		foreach ($propertyAccessProxy->getGetterConstraint()->getNamedTypeConstraints() as $namedTypeConstraint) {
			$typeName = $namedTypeConstraint->getTypeName();

			if ($typeName !== TypeName::PSEUDO_MIXED) {
				return [$typeName];
			}
		}

		$setterMethodName = PropertiesAnalyzer::buildSetterName($propertyAccessProxy->getPropertyName());
		$class = $classSetup->getClass();

		if (!$class->hasMethod($setterMethodName)) {
			return [TypeName::PSEUDO_SCALAR];
		}
		$setterMethod = $class->getMethod($setterMethodName);

		$parameters = $setterMethod->getParameters();
		if (count($parameters) == 0) {
			return [TypeName::PSEUDO_SCALAR];
		}

		foreach (TypeConstraints::type(current($parameters))->getNamedTypeConstraints() as $namedTypeConstraint) {
			$typeName = $namedTypeConstraint->getTypeName();

			if ($typeName !== TypeName::PSEUDO_MIXED) {
				return [$typeName, TypeName::PSEUDO_SCALAR];
			}
		}

		return [TypeName::PSEUDO_SCALAR];
	}
}


class EmbeddedNampingStrategy implements NamingStrategy {
	private $decoratedNamingStrategie;
	private $prefix;
	private $suffix;
	
	public function __construct(NamingStrategy $decoratedNamingStrategy, $prefix = null, $suffix = null) {
		$this->decoratedNamingStrategie = $decoratedNamingStrategy;
		$this->prefix = $prefix;
		$this->suffix = $suffix;
	}
	
	public function buildTableName(\ReflectionClass $class, string $tableName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildTableName($class, $tableName) 
				. $this->suffix;
	}

	public function buildJunctionTableName(string $ownerTableName, string $propertyName, string $tableName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildJunctionTableName($ownerTableName, 
				$propertyName, $tableName) . $this->suffix;
	}

	public function buildColumnName(string $propertyName, string $columnName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildColumnName($propertyName, 
				$columnName) . $this->suffix;
	}

	public function buildJunctionJoinColumnName(\ReflectionClass $targetClass, string $targetIdPropertyName,
			string $joinColumnName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildJunctionJoinColumnName($targetClass, 
				$targetIdPropertyName, $joinColumnName) . $this->suffix;
	}
	
	public function buildJoinColumnName(string $propertyName, string $targetIdPropertyName, string $joinColumnName = null): string {
		return $this->prefix . $this->decoratedNamingStrategie->buildJoinColumnName($propertyName, 
				$targetIdPropertyName, $joinColumnName) . $this->suffix;
	}
}
