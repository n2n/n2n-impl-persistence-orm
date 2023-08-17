<?php

namespace n2n\impl\persistence\orm\property\relation\selection;

interface ArrayObjectInitializedListener {
	function arrayObjectProxyInitialized(): void;
}