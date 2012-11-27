<?php
	class DineroMail{
		var	$ns = 'https://sandboxapi.dineromail.com/';
		var	$wsdlPath="https://sandboxapi.dineromail.com/DMAPI.asmx?WSDL";
		var $usuario = "TEST";
		var $password = "TEST-TEST-TEST-TEST-TEST";
		
		//var $ns = 'https://api.dineromail.com/';
		//var $wsdlPath="https://api.dineromail.com/DMAPI.asmx?WSDL";
		//var $usuario = "B5DD1777-ED14-4247-8722-69DA6563AA8C";
		//var $password = "8F9644985ACF6AF4256F9E927";
		
		public function cliente(){
			$soap_options = array('trace' =>1,'exceptions'=>1);	
			return new SoapClient($this -> wsdlPath,$soap_options); 	
		}
		
		public function credencial(){
			return new SOAPVar(array('APIUserName' => $this -> usuario,'APIPassword'=> $this -> password), SOAP_ENC_OBJECT, 'APICredential', $this -> ns);
		}
		
		public function mensajeId(){
			Load::lib("formato");
			return "TXTMN".Formato::ceros(rand(0,1000000),8);
		}
		
		public function balance(){
			$mensajeId = $this -> mensajeId();
			
			try
			{	
				$Hash = '1' . $mensajeId . $this -> password;
				$Hash = MD5($Hash);
				
				$cliente = $this -> cliente();
				$credencial = $this -> credencial();
			
				$request = array('Credential' => $credencial
								,'Crypt' =>  false
								,'MerchantTransactionId' => '1'
								,'UniqueMessageId' => $mensajeId
								,'Hash' => $Hash);
				
				$resultado = $cliente -> GetBalance($request);
				
				return $resultado -> GetBalanceResult;
				
			}
			catch (SoapFault $sf)
			{
				echo "Exception:". $sf->faultstring;
			}
		}
		
		public function ticketReferenciado($proveedor = "oxxo", $total = 1200){
			$mensajeId = $this -> mensajeId();
			
			try
			{
				$Hash = '1'.$mensajeId."MXN".$total.$proveedor.$this -> password;
				$Hash = MD5($Hash);
				
				$cliente = $this -> cliente();
				$credencial = $this -> credencial();
				
				$request = array('Credential' => $credencial
					,'Crypt' =>  false
					,'MerchantTransactionId' => '1'
					,'UniqueMessageId' => $mensajeId
					,'Provider' => $proveedor
					,'Amount' => $total
					,'Currency' => "MXN"
					,'Hash' => $Hash);	
	
				$resultado = $cliente -> GetPaymentTicket($request);
				
				return $resultado -> GetPaymentTicketResult;
			}
			catch (SoapFault $sf)
			{
				echo "Exception:". $sf->faultstring;
			}	
		}
		
		public function operaciones($inicio, $fin){
			$mensajeId = $this -> mensajeId();
			
			try
			{
				$Hash = "1".$mensajeId."".$inicio.$fin.$this -> password;
				$Hash = MD5($Hash);
				
				$cliente = $this -> cliente();
				$credencial = $this -> credencial();
				
				$request = array('Credential' =>$credencial
					,'Crypt' =>  false
					,'MerchantTransactionId' => "1"
					,'UniqueMessageId' => $mensajeId
					,'OperationId' => ""
					,'StartDate' => $inicio
					,'EndDate' => $fin
					,'Hash' => $Hash);	
	
				$resultado = $cliente -> GetOperations($request);
				
				return $resultado -> GetOperationsResult;
			}
			catch (SoapFault $sf)
			{
				echo "Exception:". $sf->faultstring;
			}	
		}
	}
?>