<?php

namespace Xolluteon\Wooxali\Inventory;

use Xolluteon\Wooxali\Request\Request;

class AliClient
{    
    /**
     * Access KEY
     * @var string
     */
    private $appKey;
    
    public function __construct()
    {
        $this->appKey = config('wooxali.aliexpress_app_key');
        $this->appSecret = config('wooxali.aliexpress_app_secret');
    }
    
    /**
     * Get data from AliExpress
     * @param Request $request
     */
    public function getData(Request $request)
    {
        $apiUrl = $request->getApiUrl($request->getApiRequestName(), $this->appKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request->getRequestInputParams($request)));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        $responce = curl_exec($ch);
        
        $content = json_decode($responce, false);
        
        if($content == null){
            $errorMsg = 'return null';
            $error = new \stdClass();
            $error->status = 'ERROR';
            $error->errorCode = 20020000;
            $error->errorMsg = $errorMsg;
            $content = $error;
        }

        if ( $content != null && $content->errorCode !== 20010000) {
            $errorMsg = $request->getError($content->errorCode);
            $error = new \stdClass();
            $error->status = 'ERROR';
            $error->errorCode = $content->errorCode;
            $error->errorMsg = $errorMsg;
            $content = $error;
        }
        curl_close($ch);
        
        return $content;
    }

    public function getProductDesc($url)
    {
    	$curl = curl_init();
    	// Set some options - we are passing in a useragent too here
    	curl_setopt_array($curl, array(
    	    CURLOPT_RETURNTRANSFER => 1,
    	    CURLOPT_URL => $url,
    	    // CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    	));
    	// Send the request & save response to $resp
    	$resp = curl_exec($curl);
    	// Close request to clear up some resources
    	curl_close($curl);
    	$productDesc = str_replace("window.productDescription='", "", $resp);
    	$productDesc = substr($productDesc, 15, -6);

    	$dom = new \DomDocument();
    	@$dom->loadHTML($productDesc);

    	$xp = new \DOMXPath($dom);
    	$nodes = $xp->query("//a[@name='productDetailUrl']");
    	$node = $nodes->item(0);
    	if(!empty($node)){
	        $parent = $node->parentNode; //echo "\nparent ". print_r($parent,true);	
	        $parent2 = $parent->parentNode;
	        //echo "\nparent2 ". print_r($parent2,true);//$style = $parent2->getAttribute('style'); //echo "p2 style:$style \n";	
	        $p3 = $parent2->parentNode;  //echo "Before removed\n\n\n\nShowing Html\n";	//echo $dom->saveHTML();
	        if ($p3) {
	            $removed = $p3->removeChild($parent2);
	            //echo "\n\n after removed\n\n\n\nShowing Html\n";	
	            $cleanHTML = $dom->saveHTML(); //echo $cleanHTML;
	        } else {
	            //echo "Nothing to clean "; 
	            $cleanHTML = $productDesc;
	        }
	    }else{
	        $cleanHTML = $productDesc;
	    }

	    $return = preg_replace(
                array('{<a(.*?)[^>]*><img}', '{</a>}'), array('<img', ''), $cleanHTML
        );
        $return = preg_replace('/<a href=\"(.*?)\">(.*?)<\/a>/', "\\2", $return);

        $return = str_replace("‘;", "", $return);
        $return = str_replace("';", "", $return);

    	return $return;
    }

    public function getProductProps($url)
    {
    	$curl = curl_init();
    	// Set some options - we are passing in a useragent too here
    	curl_setopt_array($curl, array(
    	    CURLOPT_RETURNTRANSFER => 1,
    	    CURLOPT_URL => $url,
    	    // CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    	));
    	// Send the request & save response to $resp
    	$resp = curl_exec($curl);
    	// Close request to clear up some resources
    	curl_close($curl);

    	$dom = new \DOMDocument();
    	@$dom->loadHTML($resp);

    	$xp = new \DOMXPath($dom);

    	# find all the script elements in the page
    	$rawProps = $xp->query("//li[contains(@class, 'property-item')]");
    	// var_dump($rawProps);
    	$props = array();
    	foreach($rawProps as $prop){
    		// var_dump($prop->nodeValue);
    		$arr = explode(':', $prop->nodeValue);
    		$key = trim($arr[0]);
    		$val = trim($arr[1]);
    		if($key == 'Brand Name'){
    			$key = 'Brand';
    		}
    		$props[$key] = $val;
    	}
    	// var_dump($props);
    	return $props;
    }

    public function getProductAttr($url)
    {
    	$curl = curl_init();
    	// Set some options - we are passing in a useragent too here
    	curl_setopt_array($curl, array(
    	    CURLOPT_RETURNTRANSFER => 1,
    	    CURLOPT_URL => $url,
    	    // CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    	));
    	// Send the request & save response to $resp
    	$resp = curl_exec($curl);
    	// Close request to clear up some resources
    	curl_close($curl);

    	$dom = new \DOMDocument();
    	@$dom->loadHTML($resp);

    	$xp = new \DOMXPath($dom);

    	# find all the script elements in the page
    	$scripts = $xp->query("//script");
    	$skus = array();
    	
    	foreach ($scripts as $s) {
    		// var_dump($s->nodeValue);
    	    # see if there are any matches for var datePickerDate in the script node's contents
    	    
    	    if (strpos($s->nodeValue, 'var skuProducts') != false) {
    	        $str = strstr($s->nodeValue, 'var skuProducts');
    	        $str = strstr($str, 'var GaData', true);
    	        $json_str = str_replace('var skuProducts=', '', $str);
    	        $json_str = str_replace('];', ']', $json_str);
    	        $arr = json_decode($json_str);

    	        for($i = 0; $i < count($arr); $i++){
                    $skuAttr = isset($arr[$i]->skuAttr) ? $arr[$i]->skuAttr : '';
                    $attrArr = explode(';', $skuAttr);
                    $strcolor = '';
                    if(is_array($attrArr) && count($attrArr) > 0){
                        foreach($attrArr as $attribute){
                            if(strpos($attribute, '#') != false) { // if it has "#" it is color of the variations.
                                $strcolor = str_replace('#', '', strstr($attribute, '#'));
                            }
                        }
                    }
                    
    	        	$skus[$i]['id'] = $arr[$i]->skuPropIds;
    	        	$skus[$i]['value'] = $arr[$i]->skuVal;
                    $skus[$i]['color'] = str_replace('#', '', $strcolor);
    	        }
    	    }
            
    	    if(strpos($s->nodeValue, 'var skuAttrIds') != false){
				$str = strstr($s->nodeValue, 'var skuAttrIds');
				$json_str = str_replace('var skuAttrIds=', '', $str);
				$json_str = str_replace('];', ']', $json_str);
				$imgarr = json_decode($json_str);
				$images = array();
				if(is_array($imgarr)){
					foreach($imgarr as $key => $val){
						$x = $key+1;
						foreach($val as $c){
							$aid = 'sku-' . $x . '-' . $c;
							$elements = $xp->query("//*[@id='".$aid."']/img");
							foreach($elements as $elem){
								$imgraw = $elem->getAttribute('bigpic');
								$img = str_replace('_640x640.jpg', '', $imgraw);
								$images[$c] = $img;
							}
						}
					}
				}
    	    }
    	}

    	//gathering all the variations
    	foreach($skus as $key => $sku){
    		$attrids = explode(',', $sku['id']);

    		if(is_array($attrids)){
    			$n = 0;
    			foreach ($attrids as $attrid) {
    				if(isset($images[$attrid])){
    					$skus[$key]['image'][$n] = $images[$attrid];
    				}
    				$n++;
    			}
    		}else{
    			if(isset($images[$attrids])){
					$skus[$key]['image'] = $images[$attrids];
				}
    		}
    	}
    	$variations = $skus;
    	return $variations;
    }

    public function getProductImages($url)
    {
    	$curl = curl_init();
    	// Set some options - we are passing in a useragent too here
    	curl_setopt_array($curl, array(
    	    CURLOPT_RETURNTRANSFER => 1,
    	    CURLOPT_URL => $url,
    	    // CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    	));
    	// Send the request & save response to $resp
    	$resp = curl_exec($curl);
    	// Close request to clear up some resources
    	curl_close($curl);

    	$dom = new \DOMDocument();
    	@$dom->loadHTML($resp);

    	$xp = new \DOMXPath($dom);

    	# find all the script elements in the page
    	$scripts = $xp->query("//script");
    	foreach ($scripts as $s) {
    		// var_dump($s->nodeValue);
    	    # see if there are any matches for var datePickerDate in the script node's contents
    	    if (strpos($s->nodeValue, 'window.runParams.imageBigViewURL') != false) {
    	        $str = strstr($s->nodeValue, 'window.runParams.imageBigViewURL');
    	        $str = strstr($str, 'window.runParams.mainBigPic', true);
    	        $json_str = str_replace('window.runParams.imageBigViewURL=', '', $str);
    	        $json_str = str_replace('];', ']', $json_str);
    	        $arr = json_decode($json_str);
    	        // var_dump($arr);
    	    }
    	}

    	return $arr;
    }
}
