<?php
class Form_Field_SimpleCheckbox extends Form_Field {
	function getInput($attr=array()){
		return parent::getInput(array_merge(
					array(
						'type'=>'checkbox',
						'value'=>'Y',
						'checked'=>$this->value=='Y'
						),$attr
					));
	}
	function loadPOST(){
		if(isset($_POST[$this->name])){
			$this->set('Y');
		}else{
			$this->set('');
		}
	}
}
