<?php
/**
 * protokollmap 
 * 	permission: in array ;; out array ;; beschlussliste
 * @var array
 */
define('PROTOMAP', [
	'stura' => ['protokoll:stura:intern', 'spielwiese:test:public2', 'spielwiese:test:beschlussliste']
]);

define('PROTO_INTERNAL_TAG', 'intern'); //negative tag is always no[tag]; here: nointern