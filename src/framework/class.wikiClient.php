<?php
/**
 * FRAMEWORK ProtocolHelper
 * XMLRPC Client
 * extends
 * 
 * for function parameter look at https://www.dokuwiki.org/devel:xmlrpc
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael gnehr
 * @since 			17.02.2018
 * @copyright 		Copyright (C) Michael Gnehr 2017, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 * 
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
	 * get docuWiki time
	 * @return string
	 */
	public function getTime(){
		$this->setMethod('dokuwiki.getTime');
		if ($this->send()){
			return $this->parse_response()[0];
		} else {
			return '';
		}
	}
	
	/**
	 * get docuWiki XMLRPC API Version
	 * @return string
	 */
	public function getXMLRPCAPIVersion(){
		$this->setMethod('dokuwiki.getXMLRPCAPIVersion');
		if ($this->send()){
			return $this->parse_response()[0];
		} else {
			return '';
		}
	}
	
	/**
	 * get docuWiki title
	 * @return string
	 */
	public function getTitle(){
		$this->setMethod('dokuwiki.getTitle');
		if ($this->send()){
			return $this->parse_response()[0];
		} else {
			return '';
		}
	}
	
	/**
	 * get docuWiki append to wikipage
	 * @param string $filename
	 * @param string $text
	 * @param array $attr
	 * @return string
	 */
	public function appendPage($filename = '', $text = '', $attr = NULL){
		$this->setMethod('dokuwiki.appendPage');
		$param=[];
		if ($filename == '' || !is_string($filename)){
			return;
		}
		$param[] = $filename;
		if (!is_string($text)){
			return;
		}
		$param[] = $text;
		if ($attr != null){
			$param[2]=['attr', $attr];
		}
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get docuWiki delete wiki page
	 * @param string $filename
	 * @return string
	 */
	public function deletePage($filename){
		return $this->appendPage($filename , '');
	}
	
	/**
	 * get docuWiki Page - raw wiki text
	 * @param string $filename
	 * @return string
	 */
	public function getPage($filename = ''){
		$this->setMethod('wiki.getPage');
		$param=[];
		if ($filename != ''){
			$param[] = $filename;
		}
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get docuWiki Page - html wiki text
	 * @param string $filename
	 * @return string
	 */
	public function getPageHTML($filename = ''){
		$this->setMethod('wiki.getPageHTML');
		$param=[];
		if ($filename != ''){
			$param[] = $filename;
		}
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get dokuWiki listAttachements
	 * @param string $namespace
	 * @param array $attr
	 * @return string
	 */
	public function listAttachements($namespace, $attr = NULL){
		$this->setMethod('wiki.getAttachments');
		$param=[];
		if ($namespace != '' || $attr != NULL){
			$param[] = $namespace;
		}
		if ($attr != null){
			$param[1]=['attr', $attr];
		}
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get dokuWiki getAttachement
	 * @param string $id file id
	 * @return string
	 */
	public function getAttachement($id){
		$this->setMethod('wiki.getAttachment');
		$param=[];
		if ($id == '' || !is_string($id)){
			return;
		}
		$param[] = $id;
		
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get dokuWiki putAttachements
	 * @return string
	 */
	/**
	 * 
	 * @param string $id file id
	 * @param string $base64 file data base64 encoded
	 * @param string $attr
	 */
	public function putAttachement($id, $base64, $attr = NULL){
		$this->setMethod('wiki.putAttachment');
		$param=[];
		if ($id == '' || !is_string($id)){
			return;
		}
		$param[] = $id;
		if ($base64 == '' || !is_string($base64)){
			return;
		}
		$param[] = $base64;
		if ($attr != null){
			$param[1]=['attr', $attr];
		}
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get dokuWiki deleteAttachement
	 * @param string $id 
	 * @return string
	 */
	public function deleteAttachement($id){
		$this->setMethod('wiki.deleteAttachment');
		$param=[];
		if ($id == '' || !is_string($id)){
			return;
		}
		$param[] = $id;
	
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result;
		} else {
			return '';
		}
	}
	
	/**
	 * get dokuWiki Pagelist
	 * @param string $namespace
	 * @param array $attr
	 * @return array|string
	 */
	public function getPagelist($namespace = '', $attr = NULL){
		$this->setMethod('dokuwiki.getPagelist');
		$param=[];
		if ($namespace != '' || $attr != NULL){
			$param[] = $namespace;
		}
		if ($attr != null){
			$param[1]=['attr', $attr];
		}
		$this->setParams($param);
		if ($this->send()){
			$this->parse_response();
			return $this->parsed_result['paths'];
		} else {
			return '';
		}
	}
	
	//==================================================================================
	// protocol helper Functions
	//==================================================================================

	/**
	 * get docuWiki Version
	 * @return array
	 */
	public function getSturaProtokolls(){
		return $this->getPagelist('protokoll:stura', ['depth' => 3]);
	}
	
	/**
	 * get docuWiki Version
	 * @return array
	 */
	public function getSturaInternProtokolls(){
		return $this->getPagelist('protokoll:stura:intern:', ['depth' => 4]);
	}
	
}

?>