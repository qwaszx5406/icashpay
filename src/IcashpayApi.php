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
		$test_mode = config('icashpay.test_mode');
		if( $test_mode ){
			$this->gateway = config('icashpay.test_gateway');
		}else{
			$this->gateway = config('icashpay.gateway');
		}	
	}
	
	public function AES_256_encript( $data ){
		// return $data;
		/*********************************************/
		if( is_array( $data ) ){
			$data = json_encode( $data );
		}
		
		$encrypt = openssl_encrypt( $data, 'aes-256-cbc', $this->AES_256_key, 0, $this->iv);
		// print_r($encrypt);
		return urlencode($encrypt);
	}
	
	public function AES_256_decript( $data ){
		// return $data;
		/*********************************************/
		$decrypt = openssl_decrypt( $data, 'aes-256-cbc', $this->AES_256_key, 0, $this->iv);
		return $decrypt;
	}
	
	private function request_post( $endpoint, $data ){
		try{
			$response = Http::withHeaders([
				'X-iCP-EncKeyID' => $this->EncKeyID,
				'X-iCP-Signature' => $this->ClientPrivateKey,	
			])->timeout(10)->post($this->gateway . '/' . $endpoint, $data );
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
	
	private function get_requset_data(){
		$data = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'Version' => $this->Version,
			'X-iCP-EncKeyID' => $this->EncKeyID,
			'X-iCP-Signature' => $this->ClientPrivateKey,
			'EncData' => [],
		];
		return $data;
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
		
		$value = $this->AES_256_encript($value);
		
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
		$data = $this->get_requset_data();
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
		$response = $this->request_post( 'CancelICPBinding', $data );
		
		if( !$response['error'] && $response['data']['RtnCode'] == '0001' ){
			$response = json_decode($response['data'], true);
			return $this->AES_256_decript($response['EncData']);
		}else{
			return $response;
		}
	}
	
	/**
	 * 綁定扣款
	 */
	public function DeductICPOB($request){
		$data = $this->get_requset_data();
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
			// 'ItemList' => [
				// [
					// 'ItemName'
					// 'Quantity'
					// 'Remark'
				// ]
			// ],
			'TradeDesc' => '',
			'ItemName' => '',
			// 'ItemType' => '',
			'ItemAmount' => '',
			'ItemQty' => '',
			'InvoiceNo' => '',
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
		$response = $this->request_post( 'DeductICPOB', $data );
		
		
		if( !$response['error'] && $response['data']['RtnCode'] == '0001' ){
			$response = json_decode($response['data'], true);
			return $this->AES_256_decript($response['EncData']);
		}else{
			return $response;
		}
	}
	
	/**
	 * 交易查詢
	 */
	public function QueryTradeICPO($request){
		$data = $this->get_requset_data();
		$EncData = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'MerchantTradeNo' => $request
		];
		
		if( is_array($request) ){
			$EncData = array_merge( $EncData, $request );
		}
		$data['EncData'] = $this->AES_256_encript($EncData);
		$response = $this->request_post( 'QueryTradeICPO', $data );
		
		
		if( !$response['error'] && $response['data']['RtnCode'] == '0001' ){
			$response = json_decode($response['data'], true);
			return $this->AES_256_decript($response['EncData']);
		}else{
			return $response;
		}
		
	}
	
	/**
	 * 退貨
	 */
	public function RefundICPO($request){
		$data = $this->get_requset_data();
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
		$response = $this->request_post( 'RefundICPO', $data );
		
		if( !$response['error'] && $response['data']['RtnCode'] == '0001' ){
			$response = json_decode($response['data'], true);
			return $this->AES_256_decript($response['EncData']);
		}else{
			return $response;
		}
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