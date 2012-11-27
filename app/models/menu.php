<?php
	class Menu extends ActiveRecord{
		public static function registrar($texto){
			$menu = new Menu();
			
			$menu -> texto = $texto;
			$menu -> abierto = "SI";
			$menu -> activo = "SI";
			
			$menu -> save();
			
			return $menu;
		}
		
		public function ventanas(){
			return Ventana::reporte("menu_id = ".$this -> id);
		}
		
		public function agregarVentana($texto, $link, $icono = "", $contenedor = "principal"){
			return Ventana::registrar($this -> id, $texto, $link, $icono, $contenedor);
		}
	}
?>