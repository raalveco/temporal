<?php

	             

	/* ================================== *

	 * PayPal Express Checkout Amecasoft  *

	 *                                    *

	 * Version 1.1                        *

	 * Fecha: 04/06/2010                  *

	 *                                    *

	 * Desarrollador:                     *

	 *   Ramiro Alonso Vera Contreras     *

	 * ================================== */

	

	class Paypalx{

		

		private $endpoint_api = "https://api-3t.paypal.com/nvp";

		private $version_api = "63.0";

		private $paypal_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";

	

		// Configuración de credenciales y permisos de API PAYPAL

    	private $usuario_api = "ventas_api1.amecasoft.com.mx";

    	private $password_api = "2KVKEPHCQDH2UP7R";

    	private $firma_api = "AFcWxV21C7fd0v3bYYYRCpSSRl31AORwB7afyFHecoeVc.eut1i.V1vn";

    

    	// Datos necesarios para el funcionamiento del proceso del Express Checkout.

    	private $divisa = "MXN";

    	private $transaccion = "Sale";

    	private $pagado = "http://www.amecasoft.com.mx/erp/paypal/pagado";

    	private $cancelado = "http://www.amecasoft.com.mx/erp/paypal/cancelado";

        

        // Datos para la personalización del Paypal

        private $tipo_pago = "Billing"; //Billing -> Tarjeta de Crédito . Login -> Paypal

        private $logo = "http://www.amecasoft.com.mx/paypal.png"; //750x90

        

        // Arreglo con los productos que contiene la venta.

        private $carrito_compra = null;

		

		//Iniciamos el proceso de compra, enviamos el monto a pagar y recibimos un token que identifica la orden de compra.

		function iniciarCheckOut($pago, $productos, $venta = 0, $meses = 0){
			
            //LLenar Carrito de Compra con productos: array(nombre,precio,cantidad)
            $carrito_compra = $productos;
            
			//'--------------------------------------------------------------------------------------------------------------- 
			//'Contruir la cadena de parametros que describe el SetExpressCheckout del API de Paypal
			//'--------------------------------------------------------------------------------------------------------------- 
			
            $parametros .= "&PAYMENTREQUEST_0_AMT=" . $pago;

            $parametros .= "&PAYMENTREQUEST_0_PAYMENTACTION=" . $this -> transaccion;

			$parametros .= "&RETURNURL=" . $this -> pagado."/".$venta."/".$meses;

			$parametros .= "&CANCELURL=" . $this -> cancelado;

			$parametros .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $this -> divisa;

            

            $parametros .= "&LANDINGPAGE=" . $this -> tipo_pago;

            $parametros .= "&HDRIMG=" . $this -> logo;

            

            $i=0; if($carrito_compra) foreach($carrito_compra as $producto){ $i++;

                $parametros .= "&L_PAYMENTREQUEST_0_NAME".$i."=".$producto["nombre"];

                $parametros .= "&L_PAYMENTREQUEST_0_AMT".$i."=".$producto["precio"];

                $parametros .= "&L_PAYMENTREQUEST_0_QTY".$i."=".$producto["cantidad"];    

            }

            

			//'--------------------------------------------------------------------------------------------------------------- 

			//' Hacer la llamada al API de Paypal

			//' Si la llamada es correcta, redireccionaremos al comprador a Paypal para autorizar el pago.

			//' Si encuentra un error, muestra el mensaje del error.

			//'--------------------------------------------------------------------------------------------------------------- 

			

			$respuesta = $this -> webService("SetExpressCheckout", $parametros);

			

			$ack = strtoupper($respuesta["ACK"]);



			if($ack=="SUCCESS")

			{

				$token = urldecode($respuesta["TOKEN"]);

			}

			else{

				$this -> imprimeError($respuesta);

			}

			

			return $respuesta["TOKEN"];

		}

		

		//Continuamos con el pago, dando el numero de orden, si el cliente acepto los terminos del pago, recibiremos un id de comprador.

		function continuarCheckOut($token){

			$parametros = "&TOKEN=" . $token;

			$respuesta = $this -> webService("GetExpressCheckoutDetails",$parametros);

		

			$ack = strtoupper($respuesta["ACK"]);



			if($ack=="SUCCESS" && isset($respuesta["PAYERID"]))

			{

				$id_comprador = urldecode($respuesta["PAYERID"]);

			}

			

			return $respuesta;

		}

		

		// Para terminar el proceso de compra enviamos el token, id del comprador y el pago establecido. La funcion regresa true si no hubo problemas para realizar la transferencia.

		function terminarCheckOut($token, $comprador, $pago){

			

			$parametros = "&TOKEN=".$token."&PAYERID=".$comprador."&PAYMENTACTION=".$this -> transaccion."&AMT=$pago&CURRENCYCODE=".$this -> divisa;



			$respuesta = $this -> webService("DoExpressCheckoutPayment",$parametros);

			

			return $respuesta;

		}

		

		//Función necesaria para comunicarse con el webservice de paypal, tomada de los ejemplos de Paypal

		function webService($servicio,$parametros)

		{

			//Configurando el CURL, es comun para todos los servicios

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL,$this -> endpoint_api);

			curl_setopt($ch, CURLOPT_VERBOSE, 1);

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

			curl_setopt($ch, CURLOPT_POST, 1);

			

		    //Parametros para el NVPRequest del servidor de Paypal

			$NVPRequest = "METHOD=" . urlencode($servicio) . "&VERSION=" . urlencode($this -> version_api) . "&PWD=" . urlencode($this -> password_api) . "&USER=" . urlencode($this -> usuario_api) . "&SIGNATURE=" . urlencode($this -> firma_api) . $parametros;

			

			//Agregar los paramtreos como campos Post

			curl_setopt($ch, CURLOPT_POSTFIELDS, $NVPRequest);

	

			//llamar al servicio web y recojo la respuesta

			$NVPResponse = curl_exec($ch);

	

			//Convertir el response y el request en arreglos

			$responseArray = $this -> NVPtoArray($NVPResponse);

			$requestArray = $this -> NVPtoArray($NVPRequest);

			

			$_SESSION['nvpReqArray']=$requestArray;

	

			if (!curl_errno($ch)) 

			{

				  curl_close($ch);

			} 

			

			return $responseArray;

		}

		

		//Funcion que convierte la respuesta dada por el webservice a un arreglo.

		function NVPtoArray($nvpstr)

		{

			$intial=0;

		 	$nvpArray = array();

	

			while(strlen($nvpstr))

			{

				//postion of Key

				$keypos= strpos($nvpstr,'=');

				//position of value

				$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

	

				/*getting the Key and Value values and storing in a Associative Array*/

				$keyval=substr($nvpstr,$intial,$keypos);

				$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);

				//decoding the respose

				$nvpArray[urldecode($keyval)] =urldecode( $valval);

				$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));

		     }

			return $nvpArray;

		}

		

		//Simple funcion para redireccionar a la pagina de paypal, se usa despues de iniciar la orden de compra y haber almacenado el token en alguna variable de session.

		function redirectPaypal($token){

			header("Location: ".$this -> paypal_url . $token);

		}

		

		//Imprime los datos regresados en una respuesta no esperada.

		function imprimeError($respuesta){

			echo '<h3 style="color: #FF0000;">LA RESPUESTA NO ES LA ESPERADA</h3>';

			echo "[TIMESTAMP] => ".$respuesta["TIMESTAMP"]."<br>";

			echo "[CORRELATIONID] => ".$respuesta["CORRELATIONID"]."<br>";

			echo "[ACK] => ".$respuesta["ACK"]."<br>";

			echo "[VERSION] => ".$respuesta["VERSION"]."<br>";

			echo "[BUILD] => ".$respuesta["BUILD"]."<br>";

			echo "[L_ERRORCODE0] => ".$respuesta["L_ERRORCODE0"]."<br>";

			echo "[L_SHORTMESSAGE0] => ".$respuesta["L_SHORTMESSAGE0"]."<br>";

			echo "[L_LONGMESSAGE0] => ".$respuesta["L_LONGMESSAGE0"]."<br>";

			echo "[L_SEVERITYCODE0] => ".$respuesta["L_SEVERITYCODE0"]."<br>";

		}

	}

?>

<?php
	/********************************************
	PayPal API Module
	 
	Defines all the global variables and the wrapper functions 
	********************************************/
	$PROXY_HOST = '127.0.0.1';
	$PROXY_PORT = '808';

	$SandboxFlag = true;

	//'------------------------------------
	//' PayPal API Credentials
	//' Replace <API_USERNAME> with your API Username
	//' Replace <API_PASSWORD> with your API Password
	//' Replace <API_SIGNATURE> with your Signature
	//'------------------------------------
	$API_UserName="univer_1332350170_biz_api1.gmail.com";
	$API_Password="1332350195";
	$API_Signature="AAi6CWPybjLDCnq96kqzJlVNxN9IAiMmDzmtD-c0wQNDghNMJ.aq5qof";

	// BN Code 	is only applicable for partners
	$sBNCode = "PP-ECWizard";
	
	
	/*	
	' Define the PayPal Redirect URLs.  
	' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
	' 	change the URL depending if you are testing on the sandbox or the live PayPal site
	'
	' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
	' For the live site, the URL is        https://www.paypal.com/webscr&cmd=_express-checkout&token=
	*/
	
	if ($SandboxFlag == true) 
	{
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
	}
	else
	{
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}

	$USE_PROXY = false;
	$version="64";

	if (session_id() == "") 
		session_start();
	/* An express checkout transaction starts with a token, that
	   identifies to PayPal your transaction
	   In this example, when the script sees a token, the script
	   knows that the buyer has already authorized payment through
	   paypal.  If no token was found, the action is to send the buyer
	   to PayPal to first authorize payment
	   */

	/*   
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
	' Inputs:  
	'		paymentAmount:  	Total value of the shopping cart
	'		currencyCodeType: 	Currency code value the PayPal API
	'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
	'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
	'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function CallShortcutExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL) 
	{
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
		
		$nvpstr="&PAYMENTREQUEST_0_AMT=". $paymentAmount;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&RETURNURL=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
		
		$_SESSION["currencyCodeType"] = $currencyCodeType;	  
		$_SESSION["PaymentType"] = $paymentType;

		//'--------------------------------------------------------------------------------------------------------------- 
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray = hash_call("SetExpressCheckout", $nvpstr);
		
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}
		   
	    return $resArray;
	}

	/*   
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
	' Inputs:  
	'		paymentAmount:  	Total value of the shopping cart
	'		currencyCodeType: 	Currency code value the PayPal API
	'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
	'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
	'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
	'		shipToName:		the Ship to name entered on the merchant's site
	'		shipToStreet:		the Ship to Street entered on the merchant's site
	'		shipToCity:			the Ship to City entered on the merchant's site
	'		shipToState:		the Ship to State entered on the merchant's site
	'		shipToCountryCode:	the Code for Ship to Country entered on the merchant's site
	'		shipToZip:			the Ship to ZipCode entered on the merchant's site
	'		shipToStreet2:		the Ship to Street2 entered on the merchant's site
	'		phoneNum:			the phoneNum  entered on the merchant's site
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function CallMarkExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, 
									  $cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState,
									  $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum
									) 
	{
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
		
		$nvpstr="&PAYMENTREQUEST_0_AMT=". $paymentAmount;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&RETURNURL=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
		$nvpstr = $nvpstr . "&ADDROVERRIDE=1";
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTONAME=" . $shipToName;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOSTREET=" . $shipToStreet;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOSTREET2=" . $shipToStreet2;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOCITY=" . $shipToCity;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOSTATE=" . $shipToState;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE=" . $shipToCountryCode;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOZIP=" . $shipToZip;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_SHIPTOPHONENUM=" . $phoneNum;
		
		$_SESSION["currencyCodeType"] = $currencyCodeType;	  
		$_SESSION["PaymentType"] = $paymentType;

		//'--------------------------------------------------------------------------------------------------------------- 
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray=hash_call("SetExpressCheckout", $nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}
		   
	    return $resArray;
	}
	
	/*
	'-------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	'
	' Inputs:  
	'		None
	' Returns: 
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'-------------------------------------------------------------------------------------------
	*/
	function GetShippingDetails( $token )
	{
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any shipping information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------
	   
	    //'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------
	    $nvpstr="&TOKEN=" . $token;

		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.  
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.  
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
	    $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
	    $ack = strtoupper($resArray["ACK"]);
		if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{	
			$_SESSION['payer_id'] =	$resArray['PAYERID'];
		} 
		return $resArray;
	}
	
	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	'
	' Inputs:  
	'		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
	' Returns: 
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function ConfirmPayment( $FinalPaymentAmt )
	{
		/* Gather the information to make the final call to
		   finalize the PayPal payment.  The variable nvpstr
		   holds the name value pairs
		   */
		

		//Format the other parameters that were stored in the session from the previous calls	
		$token 				= urlencode($_SESSION['TOKEN']);
		$paymentType 		= urlencode($_SESSION['PaymentType']);
		$currencyCodeType 	= urlencode($_SESSION['currencyCodeType']);
		$payerID 			= urlencode($_SESSION['payer_id']);

		$serverName 		= urlencode($_SERVER['SERVER_NAME']);

		$nvpstr  = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType . '&PAYMENTREQUEST_0_AMT=' . $FinalPaymentAmt;
		$nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName; 

		 /* Make the call to PayPal to finalize payment
		    If an error occured, show the resulting errors
		    */
		$resArray=hash_call("DoExpressCheckoutPayment",$nvpstr);

		/* Display the API response back to the browser.
		   If the response from PayPal was a success, display the response parameters'
		   If the response was an error, display the errors received using APIError.php.
		   */
		$ack = strtoupper($resArray["ACK"]);

		return $resArray;
	}
	
	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	This function makes a DoDirectPayment API call
	'
	' Inputs:  
	'		paymentType:		paymentType has to be one of the following values: Sale or Order or Authorization
	'		paymentAmount:  	total value of the shopping cart
	'		currencyCode:	 	currency code value the PayPal API
	'		firstName:			first name as it appears on credit card
	'		lastName:			last name as it appears on credit card
	'		street:				buyer's street address line as it appears on credit card
	'		city:				buyer's city
	'		state:				buyer's state
	'		countryCode:		buyer's country code
	'		zip:				buyer's zip
	'		creditCardType:		buyer's credit card type (i.e. Visa, MasterCard ... )
	'		creditCardNumber:	buyers credit card number without any spaces, dashes or any other characters
	'		expDate:			credit card expiration date
	'		cvv2:				Card Verification Value 
	'		
	'-------------------------------------------------------------------------------------------
	'		
	' Returns: 
	'		The NVP Collection object of the DoDirectPayment Call Response.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/


	function DirectPayment( $paymentType, $paymentAmount, $creditCardType, $creditCardNumber,
							$expDate, $cvv2, $firstName, $lastName, $street, $city, $state, $zip, 
							$countryCode, $currencyCode )
	{
		//Construct the parameter string that describes DoDirectPayment
		$nvpstr = "&AMT=" . $paymentAmount;
		$nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCode;
		$nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&CREDITCARDTYPE=" . $creditCardType;
		$nvpstr = $nvpstr . "&ACCT=" . $creditCardNumber;
		$nvpstr = $nvpstr . "&EXPDATE=" . $expDate;
		$nvpstr = $nvpstr . "&CVV2=" . $cvv2;
		$nvpstr = $nvpstr . "&FIRSTNAME=" . $firstName;
		$nvpstr = $nvpstr . "&LASTNAME=" . $lastName;
		$nvpstr = $nvpstr . "&STREET=" . $street;
		$nvpstr = $nvpstr . "&CITY=" . $city;
		$nvpstr = $nvpstr . "&STATE=" . $state;
		$nvpstr = $nvpstr . "&COUNTRYCODE=" . $countryCode;
		$nvpstr = $nvpstr . "&IPADDRESS=" . $_SERVER['REMOTE_ADDR'];

		$resArray=hash_call("DoDirectPayment", $nvpstr);

		return $resArray;
	}


	/**
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	  * hash_call: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function hash_call($methodName,$nvpStr)
	{
		$PROXY_HOST = '127.0.0.1';
	$PROXY_PORT = '808';

	$SandboxFlag = true;

	//'------------------------------------
	//' PayPal API Credentials
	//' Replace <API_USERNAME> with your API Username
	//' Replace <API_PASSWORD> with your API Password
	//' Replace <API_SIGNATURE> with your Signature
	//'------------------------------------
	$API_UserName="univer_1332350170_biz_api1.gmail.com";
	$API_Password="1332350195";
	$API_Signature="AAi6CWPybjLDCnq96kqzJlVNxN9IAiMmDzmtD-c0wQNDghNMJ.aq5qof";

	// BN Code 	is only applicable for partners
	$sBNCode = "PP-ECWizard";
	
	
	/*	
	' Define the PayPal Redirect URLs.  
	' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
	' 	change the URL depending if you are testing on the sandbox or the live PayPal site
	'
	' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
	' For the live site, the URL is        https://www.paypal.com/webscr&cmd=_express-checkout&token=
	*/
	
	if ($SandboxFlag == true) 
	{
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
	}
	else
	{
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}

	$USE_PROXY = false;
	$version="64";

	if (session_id() == "") 
		session_start();

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
	    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
	   //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
		if($USE_PROXY)
			curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT); 

		//NVPRequest for submitting to server
		$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);

		echo $nvpreq."<br>";

		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);
		
		print_r($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=deformatNVP($response);
		$nvpReqArray=deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch)) 
		{
			// moving to display page to display curl errors
			  $_SESSION['curl_error_no']=curl_errno($ch) ;
			  $_SESSION['curl_error_msg']=curl_error($ch);

			  //Execute the Error handling module to display errors. 
		} 
		else 
		{
			 //closing the curl
		  	curl_close($ch);
		}

		return $nvpResArray;
	}

	/*'----------------------------------------------------------------------------------
	 Purpose: Redirects to PayPal.com site.
	 Inputs:  NVP string.
	 Returns: 
	----------------------------------------------------------------------------------
	*/
	function RedirectToPayPal ( $token )
	{
		$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
		
		// Redirect to paypal.com here
		$payPalURL = $PAYPAL_URL . $token;
		header("Location: ".$payPalURL);
	}

	
	/*'----------------------------------------------------------------------------------
	 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
	  * It is usefull to search for a particular key and displaying arrays.
	  * @nvpstr is NVPString.
	  * @nvpArray is Associative Array.
	   ----------------------------------------------------------------------------------
	  */
	function deformatNVP($nvpstr)
	{
		$intial=0;
	 	$nvpArray = array();

		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}

?>