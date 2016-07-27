<?php

namespace App\Http\Controllers;

class GuiLogController extends BaseController {

	protected $index_create_allowed = false;
	protected $index_delete_allowed = true;
	protected $edit_view_save_button = false;


    /**
     * defines the formular fields for the edit and create view
     */
	public function view_form_fields($model = null)
	{
		// label has to be the same like column in sql table
		return array(
			array('form_type' => 'text', 'name' => 'username', 'description' => 'Username'),
			array('form_type' => 'text', 'name' => 'method', 'description' => 'Method'),
			array('form_type' => 'text', 'name' => 'model', 'description' => 'Model'),
			array('form_type' => 'text', 'name' => 'model_id', 'description' => 'ID'),
			array('form_type' => 'textarea', 'name' => 'text', 'description' => 'Changed Attributes'),
			);
	}


}