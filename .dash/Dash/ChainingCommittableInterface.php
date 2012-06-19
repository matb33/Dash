<?php

namespace Dash;

interface ChainingCommittableInterface
{
	public function __construct( CommittableInterface $committable );
}