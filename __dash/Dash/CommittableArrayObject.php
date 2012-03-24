<?php

namespace Dash;

use ArrayObject;

class CommittableArrayObject extends ArrayObject implements ChainingCommittableInterface, CommittableInterface
{
	private $committable = NULL;

	public function __construct( CommittableInterface $committable )
	{
		parent::__construct();

		$this->committable = $committable;
	}

	public function commit()
	{
		$this->committable->commit();
	}
}