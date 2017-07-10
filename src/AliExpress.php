<?php

namespace Xolluteon\Wooxali;

use Xolluteon\Wooxali\Request\PromotionLinksRequest;
use Xolluteon\Wooxali\Request\ListProductRequest;
use Xolluteon\Wooxali\Request\ProductRequest;
use Xolluteon\Wooxali\Inventory\AliClient;

class AliExpress
{   

    public function __construct($public = null, $private = null)
    {
        $this->ali_public = $public;
        $this->ali_private = $private;
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

        $request->setSort('sellerRateDown');
        $request->getHighQualityItems();

        $client = new AliClient( $this->ali_public, $this->ali_private);
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
        $client = new AliClient( $this->ali_public, $this->ali_private);
        $responce = $client->getData($request);
		//Fiqi Update response
        if(isset($responce->result) && $responce->errorCode == 20010000){
        	if ($fields == null || $fields == '') {
        		$descURL = 'https://www.aliexpress.com/getDescModuleAjax.htm?productId=' . $productId;
        		//fetch product description
        		$longDescription = $client->getProductDesc($descURL);

        		//fetch product attributes.
        		$productURL = $responce->result->productUrl;			
        		$responce->result->longDescription = $longDescription;
        	}
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
        $client = new AliClient( $this->ali_public, $this->ali_private);
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

    private function aeShortDesc($properties, $colorAttr)
    {
        $return = '';
        $return .= '<h3 style="width: 100%; position: relative; float: left;">Product Details</h3>';
        $brand = isset($properties['Brand']) ? $properties['Brand'] : 'This Product Has No Brand';
        if(is_array($colorAttr) && count($colorAttr) > 0){
        	$stringColors = implode(' | ', $colorAttr);

        	$color = $stringColors != '' ? $stringColors : 'This product does not have color attributes';
        }else{
        	$color = 'This product does not have color attributes';
        }
        
        $return .= '<h4 style="width: 100%; position: relative; float: left;">Brand: '.ucfirst($brand).'</h4>';
        $return .= '<h4 style="width: 100%; position: relative; float: left;">Color: '.ucfirst($color).'</h4>';
        $return .= '<ul style="position: relative; width: 100%; float: left; list-style-position: inside; padding-left: 0;">';
        if(is_array($properties) && count($properties) > 0){
            foreach($properties as $key => $val){
                $return .= '<li>'.$key.': '.$val.'</li>';
            }
        }else{
            // what to do on this case? - Arung
            $return .= '<li>This product does not have Product Details information</li>';
        }
        $return .= '</ul>';
       
        return $return;
    }

}
