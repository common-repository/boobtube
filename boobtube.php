<?php
/*
  Plugin Name: BoobTube
  Plugin URI: http://boobtube.take88.com
  Description: BoobTube is WordPress to display YouTube video
  Version: 1.0
  Author: Keith Vance
  Author URI: http://boobtube.take88.com
  License: GPL2
  
  Copyright 2010  Keith Vance (email: keith@take88.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.
  
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require 'widget.php';
define('BOOBTUBE_ADMIN_NONCE', 'boobtube-adminpanel-0122');
define('BOOBTUBE_HEIGHT', '151');
define('BOOBTUBE_WIDTH', '250');
define('BOOBTUBE_YOUTUBEID', 'Pn-G_pYJJfU');
define('BOOBTUBE_UPDATE_INTERVAL', '10');
define('BOOBTUBE_USERNAME', 'goodganews');

if (!class_exists("BoobTube")) {
    class BoobTube {
	var $adminOptionName;
	function __construct() {
	    $this->adminOptionName = 'BoobTube';
	}
	
	function adminOptions() {
	    $options = array(
			     'boobtube_username'=>BOOBTUBE_USERNAME,
			     'boobtube_update_interval'=>BOOBTUBE_UPDATE_INTERVAL,
			     'boobtube_default_youtubeid'=>BOOBTUBE_YOUTUBEID,
			     'boobtube_current_youtubeid'=>'',
			     'boobtube_height'=>BOOBTUBE_HEIGHT,
			     'boobtube_width'=>BOOBTUBE_WIDTH
			     );
	    $currentOptions = get_option($this->adminOptionName);
	    if (!empty($currentOptions)) {
		foreach ($currentOptions as $option=>$value) {
		    $options[$option] = $value;
		}
	    }
	    update_option($this->adminOptionName, $options);
	    return $options;
	}
	
	function init() {
	    if (is_admin()) {
		$this->adminOptions();
	    }
	}

	function updateTube() {
	    $options = get_option($this->adminOptionName);

	    // Basic RSS Parser to get the YouTubeID
	    $file = sprintf("http://gdata.youtube.com/feeds/api/users/%s/uploads/?max-results=1&prettyprint=true", $options['boobtube_username']);
	    $xml_parser = xml_parser_create();
	    // use case-folding so we are sure to find the tag in $map_array
	    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
	    xml_set_element_handler($xml_parser, "startElement", "endElement");
	    xml_set_character_data_handler($xml_parser, "characterData");
	    if (!($fp = fopen($file, "r"))) {
		die("could not open XML input");
	    }
	    
	    while ($data = fread($fp, 4096)) {
		if (!xml_parse($xml_parser, $data, feof($fp))) {
		    die(sprintf("XML error: %s at line %d",
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser)));
		}
	    }
	    xml_parser_free($xml_parser);
	    $options['boobtube_current_youtubeid'] = $GLOBALS['rss_youtubeid'];
	    $options['boobtube_lastupdate'] = time();
	    update_option($this->adminOptionName, $options);
	}
	
	function watch() {
	    $options = get_option($this->adminOptionName);
	    $lastupdate = $options['boobtube_lastupdate'];
	    $update_interval = $options['boobtube_update_interval'];

	    if (!$options['boobtube_current_youtubeid'] || !$lastupdate || (time() - $lastupdate)/60 > $update_interval) {
		$this->updateTube();
		$options = get_option($this->adminOptionName);
	    }
	    
	    if (!$options['boobtube_current_youtubeid']) {
		$youtubeid = $options['boobtube_default_youtubeid'];
	    } else {
		$youtubeid = $options['boobtube_current_youtubeid'];
	    }

	    if (!$youtubeid) {
		// If no youtube is set, fall back to plugin default
		$youtubeid = BOOBTUBE_YOUTUBEID;
	    }

	    if (!$options['boobtube_height']) {
		$height = BOOBTUBE_HEIGHT;
	    } else {
		$height = $options['boobtube_height'];
	    }
	    if (!$options['boobtube_width']) {
		$width = BOOBTUBE_WIDTH;
	    } else {
		$width = $options['boobtube_width'];
	    }
?>
	    <object width="<?php echo $width; ?>" height="<?php echo $height; ?>"><param name="movie" value="http://www.youtube.com/v/<?php echo $youtubeid; ?>&hl=en_US&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/<?php echo $youtubeid; ?>&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="<?php echo $width; ?>" height="<?php echo $height; ?>"></embed></object>
<?php
	}
	
	function printAdminPanel() {
	    $options = $this->adminOptions();
	    if ($_POST['update_boobtube_options']) {
		if (is_admin() && check_admin_referer(BOOBTUBE_ADMIN_NONCE) && $_POST['boobtube_username']) {
		    $options['boobtube_username'] = $_POST['boobtube_username'];
		    $options['boobtube_update_interval'] = $_POST['boobtube_update_interval'];
		    $options['boobtube_default_youtubeid'] = $_POST['boobtube_default_youtubeid'];
		    $options['boobtube_height'] = $_POST['boobtube_height'];
		    $options['boobtube_width'] = $_POST['boobtube_width'];
		    $options['boobtube_allowfullscreen'] = $_POST['boobtube_allowfullscreen'];
		}
		update_option($this->adminOptionName, $options);
		$this->updateTube();
		?><div class="updated"><p><strong><?php _e("Settings Updated.", "BoobTube"); ?></strong></p></div><?php
	    }
	    ?>
	    <div class="wrapper">
	    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
	    <?php 
		 if (function_exists('wp_nonce_field')) {
		     wp_nonce_field(BOOBTUBE_ADMIN_NONCE);
		 }
	    ?>
		 <h2>BoobTube Options</h2>
		 <p>
		 <label name="boobtube_username">YouTube Username: </label><input type="text" size="25" name="boobtube_username" value="<?php _e(apply_filters('format_to_edit', $options['boobtube_username']), 'BoobTube') ?>" />
		 <br />
		 <label name="boobtube_update_interval">Update interval: </label><input type="text" name="boobtube_update_interval" size="4" value="<?php _e(apply_filters('format_to_edit', $options['boobtube_update_interval']), 'BoobTube') ?>" /> in minutes
		 <br />
		 <label name="boobtube_default_youtubeid">Default YouTube ID: </label><input type="text" name="boobtube_default_youtubeid" size="20" value="<?php _e(apply_filters('format_to_edit', $options['boobtube_default_youtubeid']), 'BoobTube') ?>" />
		 <br />
		 <label name="boobtube_current_youtubeid">Current YouTube ID: </label><a href="http://www.youtube.com/watch?v=<?php echo $options['boobtube_current_youtubeid']; ?>" target="_blank"><?php echo $options['boobtube_current_youtubeid']; ?></a>
		 <br />
		 <label name="boobtube_height">Height: </label><input type="text" value="<?php echo $options['boobtube_height']; ?>" name="boobtube_height" size="4" /> <label name="boobtube_width">Width: </label><input type="text" value="<?php echo $options['boobtube_width']; ?>" name="boobtube_width" size="4" />
                 <br />                
		 <input type="submit" name="update_boobtube_options" value="<?php _e('Update Settings', 'BoobTube'); ?>" />
		 </p>
            </form>
		      <?php $this->watch(); ?>
	    </div>
<?php
	}
    }
}

if (class_exists("BoobTube")) {
    $boobtube = new BoobTube();
}

if (isset($boobtube)) {
    add_action('boobtube/boobtube.php', array(&$boobtube, 'init'));
    add_action('admin_menu', 'BoobTube_AdminPanel');
    add_action('widgets_init', 'BoobTube_Widget');
}

if (!function_exists('BoobTube_Widget')) {
    function BoobTube_Widget() {
	register_widget('BoobTube_Widget');
    }
}

if (!function_exists('BoobTube_AdminPanel')) {
    function BoobTube_AdminPanel() {
	if (!$GLOBALS['boobtube']) {
	    return;
	}
    
	if (function_exists('add_options_page')) {
	    add_options_page('BoobTube Admin Panel', 'BoobTube', 9, basename(__FILE__), array(&$GLOBALS['boobtube'], 'printAdminPanel'));
	}
    }
}

// Simple RSS YouTube Feed scanner
$rss_youtubeid = '';
$rss_entry = FALSE;
$rss_video = FALSE;

function startElement($parser, $name, $attrs)  {
    if ($name == 'ENTRY') {
	$GLOBALS['rss_entry'] = TRUE;
    }
    
    if ($GLOBALS['rss_entry'] && $name == 'ID') {
	$GLOBALS['rss_video'] = TRUE;
    }
}

function endElement($parser, $name)  {}

function characterData($parser, $data) {
    if ($GLOBALS['rss_entry'] && $GLOBALS['rss_video']) {
	$GLOBALS['rss_youtubeid'] = array_pop(explode('/', $data));
	$GLOBALS['rss_entry'] = FALSE;
	$GLOBALS['rss_video'] = FALSE;
    }
}
?>