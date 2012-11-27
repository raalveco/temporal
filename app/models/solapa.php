<?php
	class Solapa extends ActiveRecord{
		public static function registrar($ventana, $texto, $link, $contenedor = "contenido"){
			$solapa = new Solapa();
			
			$solapa -> ventana_id = $seccion;
			$solapa -> texto = $texto;
			$solapa -> link = $link;
			$solapa -> contenedor = $contenedor;
			$solapa -> activo = "SI";
			
			$solapa -> save();
			
			return $solapa;
		}
		
		public function ventana(){
			return Ventana::consultar($this -> ventana_id);
		}
	}
?>