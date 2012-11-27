<?php
	class Ventana extends ActiveRecord{
		public static function registrar($menu, $texto, $link, $icono = "", $contenedor = "principal"){
			$ventana = new Ventana();
			
			$ventana -> menu_id = $menu;
			$ventana -> texto = $texto;
			$ventana -> link = $link;
			$ventana -> icono = $icono;
			$ventana -> contenedor = $contenedor;
			$ventana -> activo = "SI";
			
			$ventana -> save();
			
			return $ventana;
		}
		
		public function menu(){
			return Menu::consultar($this -> menu_id);
		}
		
		public function solapas(){
			return Solapa::reporte("ventana_id = ".$this -> id);
		}
		
		public function agregarSolapa(){
			
		}
	}
?>