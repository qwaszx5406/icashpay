<?php
//GC_vic:國泰金流
namespace Icashpay\Api;

use Illuminate\Support\Facades\Http;

class IcashpayApi{
	
	private $PlatformID;	
	private $MerchantID;	
	private $WalletID;	
	private $ICPMID;
	private $Version;
	private $EncKeyID;
	private $ServerPublicKey;
	private $ClientPublicKey;
	private $ClientPrivateKey;
	private $AES_256_key;
	private $iv;
	private $Bind_AES_256_key;
	private $Bind_iv;
	private $gateway;		
	private $test_gateway;		
	
	public function __construct(){
		$this->PlatformID = config('icashpay.PlatformID');	
		$this->MerchantID = config('icashpay.MerchantID');	
		$this->WalletID = config('icashpay.WalletID');	
		$this->ICPMID = config('icashpay.ICPMID');
		$this->Version = config('icashpay.Version');	
		$this->EncKeyID = config('icashpay.AES_key_ID');
		$this->ServerPublicKey = config('icashpay.Server_Public_Key');
		$this->ClientPublicKey = config('icashpay.Client_Public_Key');
		$this->ClientPrivateKey = config('icashpay.Client_Private_Key');
		$this->AES_256_key = config('icashpay.AES_256_key');
		$this->iv = config('icashpay.iv');
		$this->Bind_AES_256_key = config('icashpay.bind_AES_256_key');
		$this->Bind_iv = config('icashpay.bind_iv');
		$test_mode = config('icashpay.test_mode');
		if( $test_mode ){
			$this->gateway = config('icashpay.test_gateway');
		}else{
			$this->gateway = config('icashpay.gateway');
		}	
	}
	
	function getSign($content, $privateKey){
		$privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
		wordwrap($privateKey, 64, "\n", true) .
		"\n-----END RSA PRIVATE KEY-----";
		$key = openssl_get_privatekey($privateKey);
		openssl_sign($content, $signature, $key, "SHA256");
		openssl_free_key($key);
		$sign = base64_encode($signature);
		return $sign;
	}
	
	public function Bind_AES_256_encript( $data ){
		// return $data;
		/*********************************************/
		if( is_array( $data ) ){
			$data = json_encode( $data );
		}
		
		$encrypt = openssl_encrypt( $data, 'aes-256-cbc', $this->Bind_AES_256_key, 0, $this->Bind_iv);
		// print_r($encrypt);
		return urlencode($encrypt);
	}
	
	public function AES_256_encript( $data ){
		// return $data;
		/*********************************************/
		if( is_array( $data ) ){
			$data = json_encode( $data, JSON_UNESCAPED_SLASHES );
		}
		
		$encrypt = openssl_encrypt( $data, 'aes-256-cbc', $this->AES_256_key, 0, $this->iv);
		// print_r($encrypt);
		return $encrypt;
	}
	
	public function AES_256_decript( $data ){
		// return $data;
		/*********************************************/
		$decrypt = openssl_decrypt( $data, 'aes-256-cbc', $this->AES_256_key, 0, $this->iv);
		return $decrypt;
	}
	
	private function request_post( $endpoint, $data ){
		try{
			
			$Signature = $this->getSign( $data['EncData'] , $this->ClientPrivateKey );
			$headers = [
				'X-iCP-EncKeyID' => $this->EncKeyID,
				'X-iCP-Signature' => $Signature,	
			];
			
			$url = $this->gateway . '/' . $endpoint;
			
			// $filename = 'Icashpay_request.txt';
			// $file = fopen( $filename, 'a+' );
			// fwrite( $file, "Json:\n" );
			// fwrite( $file, $this->AES_256_decript($data['EncData']) . "\n" );
			// fwrite( $file, "AES:\n" );
			// fwrite( $file, $this->AES_256_encript($this->AES_256_decript($data['EncData'])) . "\n" );
			// fwrite( $file, "AES:\n" );
			// fwrite( $file, $data['EncData'] . "\n" );
			// fwrite( $file, "AES:\n" );
			// fwrite( $file, $Signature . "\n" );
			// fwrite( $file, "X-iCP-EncKeyID:\n" );
			// fwrite( $file, $this->EncKeyID . "\n" );
			// fwrite( $file, "X-iCP-Signature:\n" );
			// fwrite( $file, $Signature . "\n" );
			// fclose($file);
		
			$response = Http::withHeaders($headers)->timeout(10)->post( $url, $data );
		
			if( $response->status() == 200 ){
				if( $response->json() == null ){
					return [
						'error' => 1,
					];
				}
				return [
					'error' => 0,
					'data' => $response->json()
				];
			}
			return [
				'error' => 1,
				'res' => $response,
				'endpoint' => $endpoint,
				'data' => $data,
			];
		}catch(\Exception $ex){
			return [
				'error' => 1,
				'res' => $ex->getMessage(),
				'endpoint' => $endpoint,
				'data' => $data,
			];
			// return false;   
        }
	}
	
	public function mainaction( $request ){
		$value = [
			'UserID' => '',
			'DisplayInformation' => '',
			'MerchantID' => $this->MerchantID,
			'MerchantTradeNo' => '',
			'ReturnURL' => '',
			'QRCodeType' => 'Bind'
		];
		if( is_array($request) ){
			$value = array_merge( $value, $request );
		}
		
		$value = $this->Bind_AES_256_encript($value);
		
		$url = sprintf( 'icashpay://www.icashpay.com.tw/ICP?Action=Mainaction&Event=ICPOB001&Value=%s&Valuetype=1', $value );
		
		return [
			'url'		  => $url,
			'qrcode_url'  => $this->generateQRfromGoogle($url),
		];
	}
	
	/**
	 * 取消綁定
	 */
	public function CancelICPBinding($request){
		$data = [];
		$EncData = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'MerchantTradeNo' => '',
			'TokenNo' => '',
			'CancelBindingDate' => ''
		];
		if( is_array($request) ){
			$EncData = array_merge( $EncData, $request );
		}
		$data['EncData'] = $this->AES_256_encript($EncData);
		$response = $this->request_post( 'Binding/CancelICPBinding', $data );
		
		$return = [
			'error' => $response['error']
		];
		if( !$response['error'] ){
			$return['StatusCode'] = $response['data']['StatusCode'];
			$return['StatusMessage'] = $response['data']['StatusMessage'];
			$return['EncData'] = $this->AES_256_decript($response['data']['EncData']);
		}else{
			$return['message'] = '交易失敗';
		}
		return $return;
	}
	
	/**
	 * 綁定扣款
	 */
	public function DeductICPOB($request){
		$data = [];
		$EncData = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'MerchantTradeNo' => '',
			// 'StoreID' => '',
			// 'StoreName' => '',
			// 'MerchantTID' => '',
			// 'PosRefNo' => '',
			'CarrierType' => 'EK0004',
			'MerchantTradeDate' => '', // yyyy/MM/dd HH:mm:ss
			'ItemAmt' => '',
			'UtilityAmt' => '',
			'CommAmt' => '',
			'ExceptAmt1' => '',
			'ExceptAmt2' => '',
			'RedeemFlag' => '',
			'BonusAmt' => '',
			'DebitPoint' => '',
			'NonRedeemAmt' => '',
			'NonPointAmt' => '',
			'ItemList' => '',
			'TradeDesc' => '',
			// 'Description' => '',
			// 'CustomField1' => '',
			// 'CustomField2' => '',
			// 'CustomField3' => '',
			'TokenNo' => ''
		];
		
		if( is_array($request) ){
			$EncData = array_merge( $EncData, $request );
		}
		$data['EncData'] = $this->AES_256_encript($EncData);
		$response = $this->request_post( 'Binding/ICPBindingDeduct', $data );
		
		$return = [
			'error' => $response['error']
		];
		if( !$response['error'] ){
			$return['StatusCode'] = $response['data']['StatusCode'];
			$return['StatusMessage'] = $response['data']['StatusMessage'];
			$return['EncData'] = $this->AES_256_decript($response['data']['EncData']);
		}else{
			$return['message'] = '交易失敗';
		}
		return $return;
	}
	
	/**
	 * 交易查詢
	 */
	public function QueryTradeICPO($request){
		$data = [];
		$EncData = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'MerchantTradeNo' => $request
		];
		
		$data['EncData'] = $this->AES_256_encript($EncData);
		$response = $this->request_post( 'Cashier/QueryTradeICPO', $data );
		
		
		$return = [
			'error' => $response['error']
		];
		if( !$response['error'] ){
			$return['RtnCode'] = $response['data']['RtnCode'];
			$return['RtnMsg'] = $response['data']['RtnMsg'];
			$return['EncData'] = $this->AES_256_decript($response['data']['EncData']);
		}else{
			$return['message'] = '交易失敗';
		}
		return $return;
		
	}
	
	/**
	 * 退貨
	 */
	public function RefundICPO($request){
		$data = [];
		$EncData = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'TransactionID' => '',
			'Amount' => '',
			'StoreID' => '',
			'StoreName' => '',
			'MerchantTradeNo' => '',
			'OMerchantTradeNo' => '',
			'MerchantTradeDate' => '',
			'BonusAmt' => '',
			'DebitPoint' => '',
			'FeeAmt' => '',
			'BillNo' => '',
			'BillItem' => '',
			'BillAmt' => '',
			'BillFee' => '',
			'BillBarcode' => '',
		];
		
		if( is_array($request) ){
			$EncData = array_merge( $EncData, $request );
		}
		$data['EncData'] = $this->AES_256_encript($EncData);
		$response = $this->request_post( 'Cashier/RefundICPO', $data );
		
		$return = [
			'error' => $response['error']
		];
		if( !$response['error'] ){
			$return['RtnCode'] = $response['data']['RtnCode'];
			$return['RtnMsg'] = $response['data']['RtnMsg'];
			$return['TransactionID'] = $response['data']['TransactionID'];
			$return['PaymentDate'] = $response['data']['PaymentDate'];
			$return['RefundAmount'] = $response['data']['RefundAmount'];
		}else{
			$return['message'] = '交易失敗';
		}
		return $return;
	}
	
	public function generateQRfromGoogle( $chl, $widhtHeight ='300' ){
		$chl = urlencode($chl); 
		return sprintf('https://chart.apis.google.com/chart?chs=%sx%s&cht=qr&chld=L|0&chl=%s',
			$widhtHeight,
			$widhtHeight,
			$chl
		);  
	}
}