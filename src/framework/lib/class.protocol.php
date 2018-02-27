<?php
/**
 * FRAMEWORK LIB Protocol
 * implement protocol class
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        lib
 * @author 			michael g
 * @author 			schlobi
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

/**
 * implement protocol class
 * @author michael g
 * @author schlobi
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 */
class Protocol
{
	/**
	 * protocol id
	 * @var int
	 */
	public $id;
	
	/**
	 * protocol url
	 * @var string
	 */
	public $url;
	
	/**
	 * protocol date
	 * @var string
	 */
	public $date;
	
	/**
	 * protocol name / url_name
	 * @var string
	 */
	public $name;
	
	/**
	 * contains array with resolution in which this protocol was accepted
	 * @var array | NULL
	 */
	public $agreed_on;
	
	/**
	 * commitee id
	 * @var int
	 */
	public $committee_id;
	
	/**
	 * committee name alias
	 * @var string
	 */
	public $committee;
	
	/**
	 * protocol public url
	 * @var string | NULL
	 */
	public $public_url;
	
	/**
	 * $draft protocol url
	 * @var string | NULL
	 */
	public $draft_url;
	
	/**
	 * current legislatur
	 * @var int
	 */
	public $legislatur;
	
	/**
	 * protocol numer in current legislatur
	 * @var int
	 */
	public $protocol_number;
	
	/**
	 * open and close tags
	 * $tags = ['tagname' => [open, closed]];
	 * array
	 */
	public $tags;
	
	/**
	 * external protocol part
	 * contains protocol text without the internal/nonpublic part
	 * string
	 */
	public $external;
	
	/**
	 * protocol preview text
	 * contains rendered html with: public<->nonpublic diff
	 * string
	 */
	public $preview;
	
	/**
	 * protocol resolutions
	 * $todos = [[text, user], [text]];
	 * array
	 */
	public $todos;
	
	/**
	 * protocol resolution list
	 * [[text, type_short, type_long, p_tag], ...]
	 * @var array
	 */
	public $resolutions;
	
	/**
	 * protocol text
	 * @var string
	 */
	public $text;
	
	/**
	 * protocol text array
	 * @var array
	 */
	public $text_a;
	
	/**
	 */
	function __construct($text)
	{
		$this->text = $text;
		$this->text_a = $output = preg_split( "/(\r\n|\n|\r)/", $text );
		
		$this->agreed_on = NULL;
		$this->tags = [];
		$this->external = '';
		$this->preview = '';
		$this->todos = [];
		$this->resolutions = [];
	}
}

?>