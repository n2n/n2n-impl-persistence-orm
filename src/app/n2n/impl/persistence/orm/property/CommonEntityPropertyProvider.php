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
use n2n\reflection\ReflectionUtils;
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
use n2n\persistence\orm\attribute\File;
use n2n\persistence\orm\attribute\ManagedFile;
use n2n\persistence\orm\attribute\OneToOne;
use n2n\persistence\orm\attribute\ManyToOne;
use n2n\persistence\orm\attribute\OneToMany;
use n2n\persistence\orm\attribute\ManyToMany;
use n2n\persistence\orm\attribute\Embedded;

class CommonEntityPropertyProvider implements EntityPropertyProvider {
	const PROP_FILE_NAME_SUFFIX = '.originalName';
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityPropertyProvider::setupPropertyIfSuitable()
	 */
	public function setupPropertyIfSuitable(AccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {

		$attributeSet = ReflectionContext::getAttributeSet($classSetup->getClass());

		$propertyName = $propertyAccessProxy->getPropertyName();
		
		if (null !== ($attrDateTime = $attributeSet->getPropertyAttribute($propertyName, DateTime::class))) {
			$classSetup->provideEntityProperty(new DateTimeEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName), array($attrDateTime)));
			return;
		}
		
		if (null !== ($attrN2nLocale = $attributeSet->getPropertyAttribute($propertyName, N2nLocale::class))) {
			$classSetup->provideEntityProperty(new N2nLocaleEntityProperty($propertyAccessProxy, 
					$attrN2nLocale->getProperty()->getName()$classSetup->requestColumn($propertyName)), array($attrN2nLocale));
			return;
		}

		if (null !== ($attrUrl = $attributeSet->getPropertyAttribute($propertyName, \n2n\persistence\orm\attribute\Url::class))) {
			$classSetup->provideEntityProperty(new UrlEntityProperty($propertyAccessProxy,
					$classSetup->requestColumn($propertyName)), array($attrUrl));
			return;
		}
		
		if (null !== ($attrFile = $attributeSet->getPropertyAttribute($propertyName, File::class))) {
			$classSetup->provideEntityProperty(new FileEntityProperty($propertyAccessProxy, 
							$classSetup->requestColumn($propertyName), 
							$classSetup->requestColumn($propertyName . self::PROP_FILE_NAME_SUFFIX)),
					array($attrFile));
			return;
		}
		
		if (null !== ($attrManagedFile = $attributeSet->getPropertyAttribute($propertyName, ManagedFile::class))) {
			$manageFileEntityProperty = new ManagedFileEntityProperty($propertyAccessProxy, 
					$classSetup->requestColumn($propertyName), $attrManagedFile->getLookupId(),
					$attrManagedFile->isCascadeDelete());
					
			if (null !== ($fileLocator = $attrManagedFile->getFileLocator())) {
				$manageFileEntityProperty->setFileLocator($fileLocator);
			} else {
				$manageFileEntityProperty->setFileLocator(new SimpleFileLocator(
						mb_strtolower(IoUtils::stripSpecialChars($classSetup->getClass()->getShortName()))));
			}
			
			$classSetup->provideEntityProperty($manageFileEntityProperty, array($attrManagedFile));
			return;
		}
		
		switch ($propertyAccessProxy->getConstraint()->getTypeName()) {
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
		}

		if ($this->checkForRelations($propertyAccessProxy, $classSetup)) {
			return;
		}
		
		if ($this->checkForEmbedded($propertyAccessProxy, $classSetup)) {
			return;
		}
		
		$setterMethodName = PropertiesAnalyzer::buildSetterName($propertyName);
		$class = $classSetup->getClass();
		
		if (!$class->hasMethod($setterMethodName)) return;
		
		$setterMethod = $class->getMethod($setterMethodName);
		
		$parameters = $setterMethod->getParameters();
		if (count($parameters) == 0) return;
		$parameter = current($parameters);
		
		if (null !== ($paramClass = ReflectionUtils::extractParameterClass($parameter))) {
			switch ($paramClass->getName()) {
				case 'DateTime':
					$classSetup->provideEntityProperty(new DateTimeEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
				case 'n2n\l10n\N2nLocale':
					$classSetup->provideEntityProperty(new N2nLocaleEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
				case 'n2n\io\managed\File':
					$classSetup->provideEntityProperty(new FileEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName), null));
					break;
				case Url::class:
					$classSetup->provideEntityProperty(new UrlEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
			}
		}
		
		if (null !== ($type = $parameter->getType())) {
			switch ($type->getName()) {
				case TypeName::BOOL:
					$classSetup->provideEntityProperty(new BoolEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
				case TypeName::INT:
					$classSetup->provideEntityProperty(new IntEntityProperty($propertyAccessProxy,
							$classSetup->requestColumn($propertyName)));
					break;
			}
		}
	}
	
	private function checkForEmbedded(AccessProxy $propertyAccessProxy,
			ClassSetup $classSetup) {
		$propertyName = $propertyAccessProxy->getPropertyName();
		$attributeSet = $classSetup->getAttributeSet();
		$attrEmbedded = $attributeSet->getPropertyAttribute($propertyName, Embedded::class);
		if ($attrEmbedded === null) return false;
		
		ArgUtils::assertTrue($attrEmbedded instanceof Embedded);
		
		$embeddedEntityProperty = new EmbeddedEntityProperty($propertyAccessProxy, 
				$attrEmbedded->getTargetClass());
				
		$classSetup->provideEntityProperty($embeddedEntityProperty);
		
		$setupProcess = $classSetup->getSetupProcess();
		$targetClassSetup = new ClassSetup($setupProcess, $attrEmbedded->getTargetClass(),
				new EmbeddedNampingStrategy($classSetup->getNamingStrategy(), $attrEmbedded->getColumnPrefix(),
						$attrEmbedded->getColumnSuffix()),
				$classSetup, $propertyName);
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
		
		if (null !== ($attrOneToOne = $attributeSet->getPropertyAttribute($propertyName, OneToOne::class))) {
			$this->provideOneToOne($propertyAccessProxy, $attrOneToOne, $classSetup);
			return true;
		}
		
		if (null !== ($attrManyToOne = $attributeSet->getPropertyAttribute($propertyName, ManyToOne::class))) {
			$this->provideManyToOne($propertyAccessProxy, $attrManyToOne, $classSetup);
			return true;
		}
		
		if (null !== ($attrOneToMany = $attributeSet->getPropertyAttribute($propertyName, OneToMany::class))) {
			$this->provideOneToMany($propertyAccessProxy, $attrOneToMany, $classSetup);
			return true;
		}
		
		if (null !== ($attrManyToMany = $attributeSet->getPropertyAttribute($propertyName, ManyToMany::class))) {
			$this->provideManyToMany($propertyAccessProxy, $attrManyToMany, $classSetup);
			return true;
		}
	}
	
	private function provideOneToOne(AccessProxy $propertyAccessProxy, 
			OneToOne $attrOneToOne, ClassSetup $classSetup) {
		$toOneProperty = new ToOneEntityProperty($propertyAccessProxy, 
				$attrOneToOne->getMappedBy() === null, RelationEntityProperty::TYPE_ONE_TO_ONE);
		$classSetup->provideEntityProperty($toOneProperty);
			
		$relationFactory = new RelationFactory($classSetup, $toOneProperty, $attrOneToOne);
			
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toOneProperty, $attrOneToOne, $relationFactory) {
			if (null !== ($mappedBy = $attrOneToOne->getMappedBy())) {
				$toOneProperty->setRelation($relationFactory->createMappedOneToOneRelation(
						$mappedBy, $entityModelManager));
			} else {
				$toOneProperty->setRelation($relationFactory
						->createMasterToOneRelation($entityModelManager));
			}
		}, $toOneProperty->isMaster());
	}
	
	private function provideManyToOne(AccessProxy $propertyAccessProxy, 
			ManyToOne $attrManyToOne, ClassSetup $classSetup) {
		$toOneProperty = new ToOneEntityProperty($propertyAccessProxy, true, 
				RelationEntityProperty::TYPE_MANY_TO_ONE);
		$classSetup->provideEntityProperty($toOneProperty);
		
		$relationFactory = new RelationFactory($classSetup, $toOneProperty, $attrManyToOne);

		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toOneProperty, $relationFactory, $classSetup) {
			$toOneProperty->setRelation($relationFactory->createMasterToOneRelation($entityModelManager));
		}, true);
	}
	
	private function provideOneToMany(AccessProxy $propertyAccessProxy,
			OneToMany $attrOneToMany, ClassSetup $classSetup) {
		$toManyProperty = new ToManyEntityProperty($propertyAccessProxy, 
				$attrOneToMany->getMappedBy() === null, RelationEntityProperty::TYPE_ONE_TO_MANY);
		$classSetup->provideEntityProperty($toManyProperty);
		
		$relationFactory = new RelationFactory($classSetup, $toManyProperty, $attrOneToMany);

		if (!$toManyProperty->isMaster()) {
			$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
					use ($toManyProperty, $attrOneToMany, $relationFactory) {
						$entityModelManager->getEntityModelByClass($attrOneToMany->getTargetEntityClass());
			}, true);
		}
			
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($toManyProperty, $attrOneToMany, $relationFactory) {
			if (null !== ($mappedBy = $attrOneToMany->getMappedBy())) {
				$toManyProperty->setRelation($relationFactory->createMappedOneToManyRelation(
						$mappedBy, $entityModelManager));
			} else {
				$toManyProperty->setRelation($relationFactory
						->createMasterToManyRelation($entityModelManager));
			}
		}, $toManyProperty->isMaster());
	}
	
	private function provideManyToMany(AccessProxy $propertyAccessProxy,
			ManyToMany $attrManyToMany, ClassSetup $classSetup) {
		$manyToManyProperty = new ToManyEntityProperty($propertyAccessProxy,
				$attrManyToMany->getMappedBy() === null, RelationEntityProperty::TYPE_MANY_TO_MANY);
		$classSetup->provideEntityProperty($manyToManyProperty);
			
		$relationFactory = new RelationFactory($classSetup, $manyToManyProperty, $attrManyToMany);
		
		$classSetup->onFinalize(function (EntityModelManager $entityModelManager)
				use ($manyToManyProperty, $attrManyToMany, $relationFactory) {
			if (null !== ($mappedBy = $attrManyToMany->getMappedBy())) {
				$manyToManyProperty->setRelation($relationFactory->createMappedManyToManyRelation(
						$mappedBy, $entityModelManager));
			} else {
				$manyToManyProperty->setRelation($relationFactory->createMasterToManyRelation(
						$entityModelManager));
			}
		}, $manyToManyProperty->isMaster());
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
