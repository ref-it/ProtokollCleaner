<?php
/**
 * FRAMEWORK ProtocolHelper
 * XMLRPC Client
 * extends
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael gnehr
 * @since 			17.02.2018
 * @copyright 		Copyright (C) Michael Gnehr 2017, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once(dirname(__FILE__, 1).'/class.xrpcClient.php');

class wikiClient extends xrpcClient
{
	//==================================================================================
	// variables
	//==================================================================================
	
	
	//==================================================================================
	// Constructor, Getter, Setter
	//==================================================================================
	
	/**
	 * constructor
	 * @param string $baseUrl
	 * @param string $username
	 * @param string $password
	 * @param string $xrpc_path
	 */
	function __construct($baseUrl, $username = "", $password = "", $xrpc_path = '/lib/exe/xmlrpc.php')
	{
		parent::__construct($baseUrl, $username, $password, $xrpc_path);
	}
	
	//==================================================================================
	// wiki Functions
	//==================================================================================
	
	/**
	 * get docuWiki Version
	 * @return string
	 */
	public function getVersion(){
		$this->setMethod('dokuwiki.getVersion');
		if ($this->send()){
			return $this->parse_response()[0];
		} else {
			return '';
		}
	}
	
	/**
	 * get docuWiki Version
	 * @return string
	 */
	public function getPagelist($param=[]){
		$this->setMethod('dokuwiki.getPagelist');
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}

    public function putPage($param = [])
    {
        $this->setMethod('wiki.putPage');
        $this->setParams($param);
        if ($this->send()) {
            $this->parse_response();
            return $this->parsed_result;
        } else {
            return '';
        }
    }
	
	//==================================================================================
	// protocol helper Functions
	//==================================================================================
	
	
	/**
	 * get docuWiki Version
	 * @return string
	 */
	public function getSturaProtokolls(){
		return $this->getPagelist(['protokoll:stura', ['attr', ['depth' => 3]]])['paths'];
	}
	
	/**
	 * get docuWiki Version
	 * @return string
	 */
	public function getSturaInternProtokolls(){
		return $this->getPagelist(['protokoll:stura:intern:', ['attr', ['depth' => 4]]])['paths'];
	}

    public function putSpielwiese($name, $inhalt)
    {
        return $this->putPage(['spielwiese:test:' . $name, $inhalt, ['attr', ['sum' => "", 'minor' => false]]]);
    }
	
	
	
	
	
	
	
	
	
	
	
	
	//==================================================================================
	// Functions
	//==================================================================================
	
	//==================================================================================
	// Functions
	//==================================================================================
	
	/**
	 * upload file from server to cloud
	 * 
	 * return codes:
	 *	  1		-> ok
	 *	 -1		-> no file
	 *	 -403	-> isuploaded-test failed
	 *	 -n		-> all other errors -> negative http response code
	 * 
	 * @param string $filepath source file path
	 * @param string $filename target file name
	 * @param boolean $testisuploaded check if file was uploaded on last post request
	 */

	public function davUploadServerFile($source_filepath, $target_filename, $testisuploaded = true){
		if (!is_file ( $source_filepath )){
			return -1;
		}
		if ($testisuploaded && !is_uploaded_file($source_filepath)){
			return -403;
		}
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $this->server.$this->target_folder.$target_filename);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
		curl_setopt($ch, CURLOPT_PUT, 1);
		
		$fh_res = fopen($source_filepath, 'r');
		
		curl_setopt($ch, CURLOPT_INFILE, $fh_res);
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($source_filepath));
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); // --data-binary
		
		$curl_response_res = curl_exec ($ch);
		fclose($fh_res);
		
		$info_code = intval(curl_getinfo ( $ch , CURLINFO_HTTP_CODE ));
		
		if ($info_code == 201) {
			return 1;
		} else {
			return -$info_code;
		}
	}
	
	public function davDownloadServerFile($dav_path, $dav_filename, $target_path, $target_filename, $disale_relative_target_folder = false){
		$path = $this->server.((!$disale_relative_target_folder)?$this->target_folder:'').(($dav_path)? $dav_path.'/':'').$dav_filename;
		$out = $target_path .((substr($target_path, -1)!='/')?'/':''). $target_filename;
		try {
			$this->_doRequest('GET', $path, array(), array (
				'sink' => $out
			));
		}
		catch (\Exception $e) {
		}
		$status = $this->getStatusCode();
		if ($status == 200){
			return true;
		} else {
			return false;
		}
	}
	
	public function davFolderExists($path = null){
		if ($path === null) $path = $this->target_folder;
		//run test
		try {
			$this->_doRequest('PROPFIND', $this->server.$path, array(
				'Depth' => 0
			), array (
				'Content-Type' => 'application/xml; charset=utf-8',
				'body' => '<?xml version="1.0" encoding="UTF-8"?>
<d:propfind xmlns:d="DAV:">
	<d:prop>
		<d:resourcetype />
	</d:prop>
</d:propfind>'
			));
		} catch (\Exception $e) {
		}
		if ($this->getStatusCode() == 207){
			return true;
		} else {
			return false;
		}
	}
	
	public function davCreateFolder($path = null){
		if ($path === null) $path = $this->target_folder;
		//run request
		try {
			$this->_doRequest('MKCOL', $this->server.$path, array(), array ());
		}
		catch (\Exception $e) {
		}
		if ($this->getStatusCode() == 201){
			return true;
		} else {
			return false;
		}
	}
	
	public function davCreateFolderRecursive($skipFirstTest = false ){
		$path_split = explode('/', $this->target_folder);
		if (end($path_split) == "") array_pop($path_split);
		$split_count = count($path_split);
		
		$create_list = array();  
		if($skipFirstTest) $create_list[] = $this->target_folder;
		for ($i = $split_count; $i > 0; $i--){
			$tmp_path = '';
			for ($j = 0; $j < $i; $j++){
				$tmp_path = (($tmp_path)? "$tmp_path/": '' ) . $path_split[$j];
			}
			$path_split[$i-1]."\n";
			
			if($i != $split_count || !$skipFirstTest){
				if (!$this->davFolderExists($tmp_path)){
					$create_list[] = $tmp_path;
				} else {
					break;
				}
			}
		}
		$create_list = array_reverse($create_list);
		$error = false;
		foreach ($create_list as $createpath){
			if (!$this->davCreateFolder($createpath)){
				$error = true;
				break;
			}
		}
		return !$error;
	}
	
	public function ocsShareFolder($publish_password){
		return $this->ocsShareFile('', $publish_password);
	}
	
	public function ocsIsPublished($filename=null){
		$path = $this->target_folder . (($filename)? $filename.((substr($filename, -1) != '/')?'/':''): '');
		//run test
		$resp = null;
		try {
			$getPath = "ocs/v2.php/apps/files_sharing/api/v1/shares";
			$this->_doRequest('GET', $this->getProtocol().'://'.$this->getServerHost().'/'. $getPath , array(
				'OCS-APIRequest' => 'true'
			), array (
				'stream' => true,
				'query' => [
					'path' => $path,
					'reshares' => 'false',
					'subfiles' => 'false'
				]
			));
		}
		catch (\Exception $e) {
		}
		if ($this->getStatusCode() == 200){
			$stream = $this->response->getBody();
			// Read the stream
			$ret = null;
			if ($stream->isReadable()){
				$ret = $stream->getContents();
			}
			if ($ret){
				if (strpos($ret, '<url>') !== false ){
					$output = preg_split("/(<url>|<\/url>)/", $ret );
					$this->lastPublishUrl = $output[1];
					return true;
				} else {
					$this->lastPublishUrl = '';
					return false;
				}
			}
			$this->lastPublishUrl = '';
			return false;
		} else {
			$this->lastPublishUrl = '';
			return false;
		}
	}
	
	public function ocsGetAllShares(){
		$getPath = "ocs/v2.php/apps/files_sharing/api/v1/shares";
		return $this->_run_ocsGetShareInfo($getPath);
	}
	
	private function _run_ocsGetShareInfo($getPath){
		$this->_doRequest('GET', $this->getProtocol().'://'.$this->getServerHost().'/'. $getPath , array(
			'OCS-APIRequest' => 'true'
		));
		$status = $this->getStatusCode();
		if ($status != 207 && $status != 200 && $status != 100){
			self::setError('Error on Dav Folder Info');
		}
		$content = $this->response->getBody()->getContents();
		$xml = new \SimpleXMLElement($content);
		unset($content);
		foreach($xml->getDocNamespaces() as $strPrefix => $strNamespace) {
			$xml->registerXPathNamespace($strPrefix,$strNamespace);
		}
		$id = $xml->xpath("//ocs/data/element/id");
		$path = $xml->xpath("//ocs/data/element/path");
		$token = $xml->xpath("//ocs/data/element/token");
		$item_type = $xml->xpath("//ocs/data/element/item_type");
		$mimetype = $xml->xpath("//ocs/data/element/mimetype");
		$file_target = $xml->xpath("//ocs/data/element/file_target");
		unset($xml);
		$count_id = count($id);
		if ($count_id != count($path) || $count_id != count($token) || $count_id != count($item_type) || $count_id != count($mimetype) || $count_id != count($file_target)){
			$this->error = 'Not well formed XML in Server response.';
			throw new \Exception($this->error);
		}
		$res = array();
		for($i = 0; $i<$count_id; $i++){
			$res[$i]['id'] = $id[$i]->__toString();
			$res[$i]['path'] = $path[$i]->__toString();
			$res[$i]['token'] = $token[$i]->__toString();
			$res[$i]['mime'] = $mimetype[$i]->__toString();
			$res[$i]['target'] = $file_target[$i]->__toString();
			$res[$i]['type'] = $item_type[$i]->__toString();
		}
		unset($id);
		unset($path);
		unset($token);
		unset($item_type);
		unset($mimetype);
		unset($file_target);
		return $res;
	}
	
	public function ocsGetShareInfoById($share_id){
		$getPath = "ocs/v2.php/apps/files_sharing/api/v1/shares/".$share_id;
		return $this->_run_ocsGetShareInfo($getPath);
	}
	
	public function davGetFileList($path, $add_target_folder = false){
		if ($path === null) $path = $this->target_folder;
		if ($add_target_folder) $path = $this->target_folder . $path;
	
		$this->_doRequest('PROPFIND', $this->server.$path, array(
			'Depth' => 1
		));
		$status = $this->getStatusCode();
		if ($status != 207 && $status != 200){
			self::setError('Error on Dav Folder Info');
		}
		$content = $this->response->getBody()->getContents();
		$xml = new \SimpleXMLElement($content);
		unset($content);
		foreach($xml->getDocNamespaces() as $strPrefix => $strNamespace) {
			$xml->registerXPathNamespace($strPrefix,$strNamespace);
		}
		$href = $xml->xpath("//d:response[count(d:propstat/d:prop/d:getcontenttype)>0]/d:href");
		$size = $xml->xpath("//d:response[count(d:propstat/d:prop/d:getcontenttype)>0]/d:propstat/d:prop/d:getcontentlength");
		$type = $xml->xpath("//d:response[count(d:propstat/d:prop/d:getcontenttype)>0]/d:propstat/d:prop/d:getcontenttype");
		unset($xml);
		if (count($href) != count($size) || count($href) != count($size)){
			$this->error = 'Not well formed XML in Server response.';
			throw new \Exception($this->error);
		}
		$count = count($href);
		$res = array();
		for($i = 0; $i<$count; $i++){
			$res[$i]['href'] = $href[$i]->__toString();
			$res[$i]['size'] = $size[$i]->__toString();
			$res[$i]['mime'] = $type[$i]->__toString();
		}
		unset($href);
		unset($size);
		unset($type);
		return $res;
	}
	
	/**
	 * pubish webdav file
	 * @param string $filename
	 * @param string $publish_password
	 * @param string $publish_path
	 * @return string empty if failed, published path to file else
	 */
	public function ocsShareFile($filename, $publish_password, $publish_path = "index.php/s/"){
		//run request
		try {
			$getPath = "ocs/v2.php/apps/files_sharing/api/v1/shares";
			$this->_doRequest('POST', $this->getProtocol().'://'.$this->getServerHost().'/'. $getPath , array(
				'OCS-APIRequest' => 'true'
			), array (
				'form_params' => [
					'path' => $this->target_folder .$filename,
					'shareType' => 3,
					'permissions' => 1,
					'publicUpload' => 'false',
					'password' => $publish_password
				]
			));

			if ($this->response->getBody()) {
				$body = $this->response->getBody();
				$output = preg_split("/(<token>|<\/token>)/", $body );
				
				$this->lastPublishUrl = $this->getProtocol().'://'.$this->getServerHost().'/'.$publish_path.$output[1];
				return $this->lastPublishUrl;
			} else {
				$this->lastPublishUrl = '';
				return "";
			}
		} catch (\Exception $e) {
			$this->lastPublishUrl = '';
			return "";
		}
	}
}

?>