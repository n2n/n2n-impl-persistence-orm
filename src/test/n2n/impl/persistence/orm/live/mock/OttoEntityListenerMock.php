<?php

namespace n2n\impl\persistence\orm\live\mock;

use n2n\context\attribute\ThreadScoped;

#[ThreadScoped]
class OttoEntityListenerMock {
	use EntityListenerMockTrait;

}