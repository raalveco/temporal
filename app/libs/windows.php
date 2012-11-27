<?php
	class Windows{
		var $template;
		
		public function Windows($plantilla = "windows_menu"){
			$this -> template = $plantilla;
		}
		
		public function renderizar(){
			echo $this -> html();
			echo $this -> javascript();
		}
		
		public function html(){
			if($this -> template == "windows_menu"){
				return $this -> htmlWindows();
			}
			
			if($this -> template == "green_menu"){
				return $this -> htmlGreen();
			}
		}
		
		public function javascript(){
			if($this -> template == "windows_menu"){
				return $this -> javascriptWindows();
			}
			
			if($this -> template == "green_menu"){
				return $this -> javascriptGreen();
			}
		}
		
		public function htmlWindows(){
			$html = '<table border="0" id="dhtmlgoodies_xpPane" style="margin-top: 0px; float: left; height: 700px;"><tr><td valing="top" style="vertical-align: top;">';
			
			$menus = Menu::reporte("activo = 'SI'");
			
			if($menus) foreach($menus as $menu){
				$html .= '<div class="dhtmlgoodies_panel"><div align="center"><br/><table border="0">';
			
				$ventanas = $menu -> ventanas();
			
				if($ventanas) foreach($ventanas as $ventana){
					$html .= '<tr>';
					$html .= '<td align="left" valign="middle" style="vertical-align:middle; font-size:11px;" width="20">';
		            $html .= Html::linkear($ventana -> link, Html::imagen("miniconos/".$ventana -> icono.".png"),$ventana -> contenedor);
		            $html .= '</td>';
					$html .= '<td class="menulink" align="left" valign="middle" style="vertical-align:middle; font-size:10px;" width="130">';
					$html .= Html::linkear($ventana -> link, $ventana -> texto,$ventana -> contenedor);
					$html .= '</td>';
					$html .= '</tr>';	
				}
				
				$html .= '</table><br/></div></div>';
			}
			
			$html .= '</td></tr><tr><td></td></tr></table>';
			
			return $html;
		}

		public function javascriptWindows(){
			$js = '<script type="text/javascript">';
			
			$titulos = "Array(";
			$estados = "Array(";
			
			$menus = Menu::reporte("activo = 'SI'");
			
			if($menus) foreach($menus as $menu){
				$titulos .= "'".$menu -> texto."',";
				
				if($menu -> abierto == "SI"){
					$estados .= "true,";	
				}
				else{
					$estados .= "false,";
				}
			}
			$titulos = substr($titulos,0,-1).")";
			$estados = substr($estados,0,-1).")";
			
			$js .= "initDhtmlgoodies_xpPane(".$titulos.",".$estados.",Array());";
			
			$js .= '</script>';
			
			return $js;
		}
		
		public function htmlGreen(){
			$html = '<ul id="main-nav">';
			$menus = Menu::reporte("activo = 'SI'");
			if($menus) foreach($menus as $menu){
				$html .= '<li>';
					$html .= Html::link($menu -> link, $menu -> texto,"class: nav-top-item");
					$ventanas = $menu -> ventanas();
					if($ventanas){
						$html .= '<ul>';
						foreach($ventanas as $ventana){
							$html .= '<li>'.Html::linkear($ventana -> link, $ventana -> texto,$ventana -> contenedor).'</li>';
						}
						$html .= '</ul>';
					}
				$html .= '</li>';
			}
			$html .= '</ul>';
			
			return $html;
		}
		
		public function javascriptGreen(){
			return "";
		}
	}
	
	class WPanel{
		var $titulo;
		var $estado;
		var $links;
		var $nlinks;
		
		public function WPanel($titulo, $estado = true){
			$this -> titulo = $titulo;
			$this -> links = array();
			$this -> nlinks = 0;
			$this -> estado = $estado;
		}
		
		public function link($url,$texto,$icono,$ajax = false, $target = false){
			$this -> links[$this -> nlinks] = new WLink($url,$texto,$icono,$ajax,$target);
			
			return $this -> links[$this -> nlinks++];
		}
		
		public function addLink($link){
			$this -> links[$this -> nlinks++] = $link;
		}
		
		public function html(){
			$html = '<div class="dhtmlgoodies_panel"><div align="center"><br/><table border="0">';
			
			if($this -> links) foreach($this -> links as $link){
				$html .= $link -> html();	
			}
			
			$html .= '</table><br/></div></div>';
			
			return $html;
		}
	}
	
	class WLink{
		var $url;
		var $texto;
		var $icono;
		var $ajax;
		
		public function WLink($url,$texto,$icono,$ajax = false){
			$this -> url = $url;
			$this -> texto = $texto;
			$this -> icono = $icono;
			$this -> ajax = $ajax;
		}
		
		public function html(){
			$html = '<tr>';
			$html .= '<td align="left" valign="middle" style="vertical-align:middle; font-size:11px;" width="20">';
            $html .= $this -> ajax ? Html::linkAjax($this -> url,Html::imagen($this -> icono),$this -> ajax) : Html::link($this -> url,Html::imagen($this -> icono),"target: _blank");
			$html .= '</td>';
			$html .= '<td class="menulink" align="left" valign="middle" style="vertical-align:middle; font-size:10px;" width="130">';
			$html .= $this -> ajax ? Html::linkAjax($this -> url,$this -> texto,$this -> ajax) : Html::link($this -> url,$this -> texto,"target: _blank");
			$html .= '</td>';
			$html .= '</tr>';
			
			return $html;
		}
	}
?>