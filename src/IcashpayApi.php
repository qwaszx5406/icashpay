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
		print_r($encrypt);
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
			dump($response);
			if( $response->status() == 200 ){
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
	
	/**
	 * 取得綁定特電通知
	 */
	public function ICPBindingNotify_response()
    {
		$data = file_get_contents('php://input');
		
		
		return $response;
		$response = json_decode($data, true);
		return $this->AES_256_decript($response['EncData']);
	}
	
	/**
	 * 取消綁定
	 */
	public function CancelICPBinding($request){
		$data = $this->get_requset_data();
		$data['EncData'] = $this->AES_256_encript($request);
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
		$data['EncData'] = $this->AES_256_encript($request);
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
		$data['EncData'] = $this->AES_256_encript($request);
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
		$data['EncData'] = $this->AES_256_encript($request);
		$response = $this->request_post( 'RefundICPO', $data );
		
		return $response;
		$response = json_decode($response, true);
		return $this->AES_256_decript($response['EncData']);
	}
}