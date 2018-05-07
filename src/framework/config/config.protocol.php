<?php
/**
 * protokollmap 
 * 	permission: in array ;; out array ;; beschlussliste ;; invitation mail target
 * @var array
 */
const PROTOMAP = [
	'stura' => ['protokoll:stura:intern', 'protokoll:stura', 'stura:intern:beschluesse', 'sturaete@tu-ilmenau.de']
];

define('PROTO_INTERNAL_TAG', 'intern'); //negative tag is always no[tag]; here: nointern
