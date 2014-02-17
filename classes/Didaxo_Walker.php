<?php 

namespace TU;

!defined( 'ABSPATH' ) and exit;

class DidaxoLevelWalker extends \Walker_Page
{

	public static $_index = 1;

	// Displays start of an element. E.g '<li> Item Name'
    // @see Walker::start_el()
    function start_el(&$output, $item, $depth=0, $args=array()) {
        $output .= "<li><span class='levelId-". $item->ID . "'>Parte " . self::$_index++;
    }
 
    // Displays end of an element. E.g '</li>'
    // @see Walker::end_el()
    function end_el(&$output, $item, $depth=0, $args=array()) {
        $output .= "</span></li>\n";
    }
}