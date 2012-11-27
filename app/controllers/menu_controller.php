<?php
	class MenuController extends ApplicationController 
	{
		public function index(){
			$this -> set_response("view");
		}	
		
		public function registro(){
			$this -> set_response("view");
			$this -> render("menu_form",null);
		}
		
		public function registrar(){
			$this -> render(null,null);
			
			Load::lib("formato");
			
			$menu = false;
			$menu = Menu::registrar(Formato::capital(Formato::minusculas($this -> post("titulo"))));
			
			if($menu){
				$menu -> abierto = $this -> post("abierto");
				$menu -> activo = $this -> post("activo");
			}
		}
		
		public function reporte(){
			$this -> set_response("view");
			$this -> render("menu_report",null);
			
			$this -> menus = Menu::reporte();
		}
		
		public function consulta($id, $nuevo = false){
			$this -> set_response("view");
			$this -> render("menu_form",null);
			
			$this -> menu = Menu::consultar($id); 
		}
	}
?>