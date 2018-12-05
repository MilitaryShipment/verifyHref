<?php

//todo HOWTO RECURSIFY


$v = new VerifyHref("http://www.militaryshipment.com");
print_r($v->exceptions);
print_r($v->results);
//$v = new VerifyHref("http://ms-ubuntu-01/");
class VerifyHref{

    const HREF = '/a.*href="(.*?)"/';
    const DOMAIN = '/http[s]?:\/\/(.*)\/?/';
    const TRUEWEB = '/www/';
    const DYNWEB = '/\/\/www/';
    const ROOTSLASH = "/";
    const ANCHOR = "/^#/";
    const MAILTO = "/mailto:/";
    const TELTO = "/tel:/";
    const GOODRESPONSE = 200;
    const FOUNDRESPONSE = 302;
    const BADQUERY = 400;

    public $exceptions = array();
    public $results = array();
    protected $allowableResponses = array(self::GOODRESPONSE,self::FOUNDRESPONSE,self::BADQUERY);
    protected $domain;

    public function __construct($url){
        $this->runCount = 0;
        $this->domain = $this->_extractDomain($url);
        $links = $this->_extractLinks($this->_httpCall($url));
        foreach($links as $link){
            if(!$this->_isContactLink($link)){
                $html = $this->_httpCall($this->_cleanLink($link));
                $moreLinks = $this->_extractLinks($html);
                if(!$moreLinks){
                    continue;
                }else{
                    foreach($moreLinks as $anotherLink){
                        if(!$this->_isContactLink($anotherLink)){
                            $html = $this->_httpCall($this->_cleanLink($anotherLink));
                        }
                    }
                }
            }
        }
    }
    protected function _httpCall($url){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if(!in_array($http_code,$this->allowableResponses)){
            $this->exceptions[] = array($url,$http_code);
            return false;
        }
        if(!in_array($url,$this->results)){
            $this->results[] = $url;
        }
        return $output;
    }
    protected function _extractLinks($rawHtml){
        if(!$rawHtml){
            return false;
        }
        $links = array();
        preg_match_all(self::HREF,$rawHtml,$links);
        return $links[1];
    }
    protected function _isContactLink($linkStr){
        if(preg_match(self::MAILTO,$linkStr) || preg_match(self::TELTO,$linkStr)){
            return true;
        }
        return false;
    }
    protected function _extractDomain($url){
        $matches = array();
        preg_match(self::DOMAIN,$url,$matches);
        return $matches[1];
    }
    protected function _cleanLink($linkStr){
        if($linkStr == self::ROOTSLASH){
            return $this->domain;
        }elseif(preg_match(self::ANCHOR,$linkStr)){
            return $this->domain . self::ROOTSLASH . $linkStr;
        }elseif(!preg_match(self::DOMAIN,$linkStr) && preg_match(self::DYNWEB,$linkStr)){
            return preg_replace(self::DYNWEB,"www",$linkStr);
        }elseif(!preg_match(self::DOMAIN,$linkStr) && preg_match(self::TRUEWEB,$linkStr)){
            return $linkStr;
        }elseif(!preg_match(self::DOMAIN,$linkStr)){
            return $this->domain . $linkStr;
        }
        return $linkStr;
    }
}
