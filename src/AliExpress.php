<?php

namespace Xolluteon\Wooxali;

use Xolluteon\Wooxali\Request\PromotionLinksRequest;
use Xolluteon\Wooxali\Request\ListProductRequest;
use Xolluteon\Wooxali\Request\ProductRequest;
use Xolluteon\Wooxali\Inventory\AliClient;

class AliExpress
{    
    public function __construct()
    {

    }
    
    /**
     * Get list Product
     * @param string $searchParam CategoryID or Keywords
     * (The category ID for the products. Add the level-1 category description
     * below for your reference.<br/>Product title must contain a relevant keyword.
     * Note: You should select at least one of the parameters, keywords or
     * category ID, when you run a query.) 
     * @param array $params <br/>
     * @return mixed
     */
    public function getListProduct($searchParam, $params=array()){
        //require_once 'request/ListProductRequest.php';
        $request = new ListProductRequest;
        if (is_numeric($searchParam)) {
            $request->setCategoryId($searchParam);
        } else {
            $request->setKeywords($searchParam);
        }

        foreach ($params as $key => $val) {
            $set = 'set'.ucfirst($key);
            $request->$set($val);
        }
        $client = new AliClient;
        $responce = $client->getData($request);
        
        
        return $responce;
    }
    
    /**
     * Get Product
     * @param string $productId The product ID
     * @param string $fields List of fields needed to return. Please separate 
     * each other with an English comma “,” if you want to use more than one field.
     * @return mixed
     */
    public function getProduct($productId, $fields = null){
        //require_once 'request/ProductRequest.php';
        $request = new ProductRequest;
        $request->setProductId($productId);
        if ($fields !== null) {
            $request->setFields($fields);
        }
        $client = new AliClient;
        $responce = $client->getData($request);
		//Fiqi Update response
        if($responce && $responce->errorCode == 20010000){

                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://www.aliexpress.com/getDescModuleAjax.htm?productId='.$productId,
                    // CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                ));
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                // Close request to clear up some resources
                curl_close($curl);
                $productDesc = str_replace("window.productDescription='", "", $resp);
                $productDesc = substr($productDesc, 15, -6);
                $responce->result->productDescription = $productDesc;
        }
        return $responce;
    }
    
    /**
     * Get Promotion Links
     * @param string $trackingId The tracking ID of your account in the Portals platform.
     * @param string $urls The list of URLs need to be converted to promotion URLs. 
     * Please separate each URL with an English “,” if you want to use more than 
     * one field. The limit of URL’s that can be used is 50.
     * @param string $fields List of fields needed to return, options including 
     * “tracking ID” , ”publisher ID” , ”promotion URL”. Please separate each 
     * other with “,” if you want to use more than one field.
     * @return type
     */
    public function getPromotionLinks($trackingId, $urls, $fields = null){
        //require_once 'request/PromotionLinksRequest.php';
        $request = new PromotionLinksRequest;
        $request->setTrackingId($trackingId);
        $request->setUrls($urls);
        if ($fields !== null) {
            $request->setFields($fields);
        }
        $client = new AliClient;
        $responce = $client->getData($request);
        
        return $responce;
    }
    
    /**
     * Get Category
     * @return array
     */
    public function getListCategory(){

        $catList = config('wooxali.category');
        return $catList;
    }
}
