<?php

if ( function_exists( 'wfLoadExtension' ) ) {
    wfLoadExtension( 'Swagger' );
    return true;
} else {
    die( 'This version of the TemplateData extension requires MediaWiki 1.25+' );
}
