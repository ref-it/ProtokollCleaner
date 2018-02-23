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
	 * protocol name
	 * @var string
	 */
	public $name;
	
	/**
	 * pointer to resolution
	 * @var int | NULL
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
	 * open and close tags
	 * $tags = ['tagname' => [open, closed]];
	 * array
	 */
	public $tags;
	
	/**
	 * internal protocol part
	 * $internal = [[open, closed], [open, closed]];
	 * array
	 */
	public $internal;
	
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
	}
}

?>