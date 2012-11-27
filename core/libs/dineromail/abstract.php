<?php

	abstract class DineroMail_Gateway_Abstract {
		protected $_nameSpace = "";
		protected $_wdsl = "";
	
	
		public function getNameSpace() {
			return $this->_nameSpace;
		}
	
		public function getWdsl() {
			return $this->_wdsl;
		}
	
	
	}

?>