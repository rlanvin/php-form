<?php

class FormTest extends PHPUnit_Framework_TestCase
{
	public function testSubForm()
	{
		$data = array(
			'id' => 42,
			'data' => array(
				'first_name' => 'Bruce',
				'last_name' => 'Wayne'
			)
		);

		$form = new Form(array(
			'id' => array(),
			'data' => new Form(array(
				'first_name' => array(),
				'last_name' => array()
			))
		));
		$this->assertTrue($this->form->validates($data));
	}
}
