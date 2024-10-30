<?php
class BoobTube_Widget extends WP_Widget {
	function BoobTube_Widget() {
	    $widget_ops = array('classname' => 'boobtube', 'description' => 'My Video');
	    $control_ops = array('width' => '300', 'height' => '350', 'id_base' => 'boobtube-widget');
	    $this->WP_Widget('boobtube-widget', 'Boob Tube', $widget_ops, $control_ops);
	}
 
	function widget($args, $instance) {
	    extract($args);
	    $title = apply_filters('widget_title', $instance['title'] );
	    // Controlled by theme.
	    echo $before_widget;
	    if ($title) {
		echo $before_title . $title . $after_title;
	    }
	    $bt =& $GLOBALS['boobtube'];
	    $bt->watch();
	    // Controlled by theme.
	    echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
	    $instance = $old_instance;
	    $instance['title'] = strip_tags($new_instance['title']);
	    return $instance;
	}
 
	function form($instance) {
	    $defaults = array( 'title' => 'My Video');
	    $instance = wp_parse_args((array) $instance, $defaults);
	    $title = htmlspecialchars($instance['title']);
	    echo "<p>\n<label for=\"" . $this->get_field_name('title') . "\">Title:</label>\n";
	    echo "\n<input type=\"text\" id=\"" . $this->get_field_id('title') . "\" name=\"" . $this->get_field_name('title') . "\" value=\"" . $title . "\" style=\"width:100%\" />\n</p>\n";
	}
}
?>