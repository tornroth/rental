<?php
/**
 * Theme related functions. 
 *
 */

/**
 * Get title for the webpage by concatenating page specific title with site-wide title.
 *
 * @param string $title for this page.
 * @return string $title with append if set.
 */
function get_title($title) {
  global $herbert;
  return $title . (isset($herbert['title_append']) ? $herbert['title_append'] : null);
}



/**
 * Render the menu.
 *
 * @param array $meny content in the menu.
 * @param string $class name of the class in the menu.
 * @return string $html the menu in html to insert in current page.
 */
function get_menu($menu) {
  global $herbert;
  if(isset($menu['callback'])) {
    $items = call_user_func($menu['callback'], $menu['items']);
  }
  $html = "<nav>\n";
  foreach($items as $item) {
    $class = isset($item['class']) ? " class='{$item['class']}'" : "";
    $html .= "<a href='{$item['url']}'{$class}>{$item['text']}</a>\n";
  }
  $html .= "</nav>\n";
  return $html;
}



/**
 * A callback function to highlight current page in menu.
 *
 * @param array $items content in the menu.
 * @return array $items modified content in the menu.
 */
function modifyNavbar($items) {
  $ref = basename($_SERVER['REQUEST_URI']) == "webroot" ? "home" : basename($_SERVER['REQUEST_URI'], ".php");
  if(array_key_exists($ref, $items)) {
    $items[$ref]['class'] .= 'selected'; 
  }
  return $items;
}

