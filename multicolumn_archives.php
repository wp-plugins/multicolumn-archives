<?php
/**
* Plugin Name: Multicolumn Archives
* Description: Multicolumn archives widget
* Version: 1.0
* Author: Milosz Galazka
* Author URI: http://www.sleeplessbeastie.eu
*/

add_action('widgets_init', 'multicolumn_archives_load_widgets');

function multicolumn_archives_load_widgets() {
  register_widget('Multicolumn_Archives');
}

class Multicolumn_Archives extends WP_Widget {

  function Multicolumn_Archives() {
    $widget_ops = array('classname' => 'multicolumn_archives', 'description' => __('Multicolumn archives widget.', 'multicolumn_archives'));
    $control_ops = array('id_base' => 'multicolumn_archives');
    $this->WP_Widget('multicolumn_archives', __('Multicolumn Archives', 'multicolumn_archives'), $widget_ops, $control_ops);
  }

  function widget($args, $instance) {
    global $wpdb, $wp_locale;
    extract($args);

    // used variables
    $title                     = apply_filters('widget_title', $instance['title']);
    $show_months_without_posts = $instance['show_months_without_posts'];
    $show_post_counts          = $instance['show_post_counts'];
    $column_width              = apply_filters('widget_text', $instance['column_width']);
    $column_margin             = apply_filters('widget_text', $instance['column_margin']);

    // set sql query depending on "Show post counts" setting
    if($show_post_counts == "on") {
      // count posts by year and month and sort it
      // year | month | count
      // 2012 |    12 | 3
      // 2012 |    11 | 5
      // 2012 |    10 | 4
      // 2012 |     9 | 6
      // ...
      $query = "select DISTINCT year(post_date) as year, month(post_date) as month, count(*) as count from $wpdb->posts
                where post_status=\"publish\"
                group by year, month order by year DESC, month DESC";
    } else {
      // do not count posts
      // year | month
      // 2012 |    12
      // 2012 |    11
      // 2012 |    10
      // 2012 |     9
      // ...
      $query = "select distinct year(post_date) as year, month(post_date) as month from $wpdb->posts
                where post_status=\"publish\"
                order by year DESC, month DESC";
    }

    $rows = $wpdb->get_results($query);

    foreach($rows as $row) {
      $years_associated_with_months["$row->year"]["$row->month"] = isset($row->count) ? $row->count : 1;
    }

    $html_output  = "";
    $html_output .= $before_widget;

    if($title)
      $html_output .= $before_title . $title . $after_title;

    foreach($years_associated_with_months as $year => $months) {
      $html_output .= $this->helper_open_year($year, $column_width, $column_margin);
      for($month = 12;$month >= 1;$month--){
        $html_output .= $this->helper_month($year, $month, $show_months_without_posts, $show_post_counts, $months["$month"]);
      }
      $html_output .= $this->helper_close_year();
    }

    // use 'clear:both' as we use 'float:left' in helper_open_year
    $html_output .= "<div style=\"clear:both\"></div>";
    $html_output .= $after_widget;
    echo $html_output;
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;

    $instance['title']                     = strip_tags($new_instance['title']);
    $instance['column_width']              = strip_tags($new_instance['column_width']);
    $instance['column_margin']             = strip_tags($new_instance['column_margin']);
    $instance['show_months_without_posts'] = $new_instance['show_months_without_posts'];
    $instance['show_post_counts']          = $new_instance['show_post_counts'];

    return $instance;
  }

  function form($instance) {
    $defaults = array('title' => __('Archives', 'multicolumn_archives'), 'show_months_without_posts' => true, 'show_post_counts' => false, 'column_width' => "100px", 'column_margin' => '0 5px 15px 0');
    $instance = wp_parse_args((array) $instance, $defaults);

    $html_output  = "";

    $html_output .= "<p>";
    $html_output .= "<input class=\"checkbox\" type=\"checkbox\"" . checked((bool) $instance['show_months_without_posts'], true, false) . " id=\"" . $this->get_field_id('show_months_without_posts') . "\" name=\"" . $this->get_field_name('show_months_without_posts') . "\" /> ";
    $html_output .= "<label for=\"" . $this->get_field_id('show_months_without_posts') . "\">" .  __('Show months without posts', 'multicolumn_archives') . "</label>";
    $html_output .= "</p>";

    $html_output .= "<p>";
    $html_output .= "<input class=\"checkbox\" type=\"checkbox\"" . checked((bool) $instance['show_post_counts'], true, false) . " id=\"" . $this->get_field_id('show_post_counts') . "\" name=\"" . $this->get_field_name('show_post_counts') . "\" /> ";
    $html_output .= "<label for=\"" . $this->get_field_id('show_post_counts') . "\">" .  __('Show post counts', 'multicolumn_archives') . "</label>";
    $html_output .= "</p>";

    $html_output .= "<p>";
    $html_output .= "<label for=\"" . $this->get_field_id('title') . "\">" . __('Title:', 'multicolumn_archives') . "</label> ";
    $html_output .= "<input id=\"" . $this->get_field_id('title') . "\" name=\"" . $this->get_field_name('title') . "\" value=\"" . $instance['title'] . "\" class=\"widefat\" type=\"text\" />";
    $html_output .= "</p>";

    $html_output .= "<p>";
    $html_output .= "<label for=\"" . $this->get_field_id('column_width') . "\">" . __('Column width:', 'multicolumn_archives') . "</label> ";
    $html_output .= "<input id=\"" . $this->get_field_id('column_width') . "\" name=\"" . $this->get_field_name('column_width') . "\" value=\"" . $instance['column_width'] . "\" class=\"widefat\" type=\"text\" />";
    $html_output .= "<small><em>";
    $html_output .= "For example \"<strong>100px</strong>\" or \"<strong>42%</strong>\"";
    $html_output .= "</em></small>";
    $html_output .= "</p>";

    $html_output .= "<p>";
    $html_output .= "<label for=\"" . $this->get_field_id('column_margin') . "\">" . __('Column margin:', 'multicolumn_archives') . "</label> ";
    $html_output .= "<input id=\"" . $this->get_field_id('column_margin') . "\" name=\"" . $this->get_field_name('column_margin') . "\" value=\"" . $instance['column_margin'] . "\" class=\"widefat\" type=\"text\" />";
    $html_output .= "<small><em>";
    $html_output .= "For example \"<strong>2%</strong>\" or \"<strong>0 5px 10px 0</strong>\"";
    $html_output .= "</em></small>";
    $html_output .= "</p>";

    echo $html_output;
  }

  function helper_open_year($year, $column_width, $column_margin) {
    $html_output .= "<div style=\"float:left;width:" . $column_width . ";margin: " . $column_margin . ";\">";
    $html_output .= "<ul>";
    $html_output .= "<lh>" . $year . "</lh>";
    return $html_output;
  }

  function helper_close_year() {
    $html_output .= "</ul>";
    $html_output .= "</div>";
    return $html_output;
  }

  function helper_month($year, $month, $show_months_without_posts, $show_post_counts, $post_count){
    global $wp_locale;

    if($show_post_counts == "on")
      if($post_count >= 1)
        $html_count = " (" . $post_count . ")";
      else
        $html_count = " (0)";
    else
      $html_count = "";

    if($post_count >= 1) {
      $html_output .= "<li><a href=\"" . get_month_link($year, $month) . "\">" . $wp_locale->get_month($month)  . $html_count . "</a></li>";
    } else {
      if($show_months_without_posts == "on")
        $html_output .= "<li>" . $wp_locale->get_month($month) . $html_count . "</li>";
    }
    return $html_output;
  }
}
?>