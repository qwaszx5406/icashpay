<?php
//GC_vic:國泰金流
namespace Icashpay\Api;

use Illuminate\Support\Facades\Http;

class IcashpayApi{
	
	private $PlatformID;	
	private $MerchantID;	
	private $WalletID;	
	private $Version;
	private $EncKeyID;
	private $ClientPrivateKey;
	private $ICP_public_key;
	private $AES_256_key;
	private $gateway;		
	private $test_gateway;		
	
	public function __construct(){
		$this->PlatformID = config('icashpay.PlatformID');	
		$this->MerchantID = config('icashpay.MerchantID');	
		$this->WalletID = config('icashpay.WalletID');	
		$this->Version = config('icashpay.Version');	
		$this->EncKeyID = config('icashpay.EncKeyID');
		$this->ClientPrivateKey = config('icashpay.ClientPrivateKey');
		$this->ICP_public_key = config('icashpay.ICP_public_key');
		$this->AES_256_key = config('icashpay.AES_256_key');
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
		$iv_length = openssl_cipher_iv_length('AES-256-CBC');
		$iv = openssl_random_pseudo_bytes($iv_length);
		$encrypt = openssl_encrypt( $data, 'AES-256-CBC', $this->AES_256_key, 0, $iv);
		// print_r($encrypt);
		return base64_encode($encrypt);
	}
	
	public function AES_256_decript( $data ){
		// return $data;
		/*********************************************/
		$data = base64_decode( $data );
		$decrypt = openssl_decrypt( $data, 'AES-256-CBC', $this->AES_256_key, 0);
		return $decrypt;
	}
	
	private function request_post( $endpoint, $data ){
		try{
			$response = Http::timeout(10)->post($this->gateway . '/' . $endpoint, $data );
			if( $response->status() == 200 ){
				if( $response->json() == null ){
					return false;
				}
				return $response->json();
			}else{
				return false;
			}
		}catch(\Exception $ex){
			// return $ex;   
			return false;   
        }
	}
	
	private function get_requset_data(){
		$data = [
			'PlatformID' => $this->PlatformID,
			'MerchantID' => $this->MerchantID,
			'WalletID' => $this->WalletID,
			'Version' => $this->Version,
			'X-iCP-EncKeyID' => $this->AES_256_key,
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
			'ReturnURL' => ''
		];
		if( is_array($request) ){
			$value = array_merge( $value, $request );
		}
		$value = $this->AES_256_encript($value);
		
		return "icashpay://www.icashpay.com.tw/ICP?Action=Mainaction&Event=ICPOB001&Value=" . $value . "&Valuetype=1";
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
		
		return $response;
		$response = json_decode($response, true);
		return $this->AES_256_decript($response['EncData']);
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
		
		return $response;
		$response = json_decode($response, true);
		return $this->AES_256_decript($response['EncData']);
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
			'MerchantTradeNo' => ''
		];
		
		if( is_array($request) ){
			$EncData = array_merge( $EncData, $request );
		}
		$data['EncData'] = $this->AES_256_encript($EncData);
		$response = $this->request_post( 'QueryTradeICPO', $data );
		
		return $response;
		$response = json_decode($response, true);
		return $this->AES_256_decript($response['EncData']);
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
		
		return $response;
		$response = json_decode($response, true);
		return $this->AES_256_decript($response['EncData']);
	}
}