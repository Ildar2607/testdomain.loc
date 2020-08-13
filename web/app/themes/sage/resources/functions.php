<?php

/**
 * Do not edit anything in this file unless you know what you're doing
 */

use Roots\Sage\Config;
use Roots\Sage\Container;

/**
 * Helper function for prettying up errors
 * @param string $message
 * @param string $subtitle
 * @param string $title
 */
$sage_error = function ($message, $subtitle = '', $title = '') {
    $title = $title ?: __('Sage &rsaquo; Error', 'sage');
    $footer = '<a href="https://roots.io/sage/docs/">roots.io/sage/docs/</a>';
    $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";
    wp_die($message, $title);
};

/**
 * Ensure compatible version of PHP is used
 */
if (version_compare('7.1', phpversion(), '>=')) {
    $sage_error(__('You must be using PHP 7.1 or greater.', 'sage'), __('Invalid PHP version', 'sage'));
}

/**
 * Ensure compatible version of WordPress is used
 */
if (version_compare('4.7.0', get_bloginfo('version'), '>=')) {
    $sage_error(__('You must be using WordPress 4.7.0 or greater.', 'sage'), __('Invalid WordPress version', 'sage'));
}

/**
 * Ensure dependencies are loaded
 */
if (!class_exists('Roots\\Sage\\Container')) {
    if (!file_exists($composer = __DIR__.'/../vendor/autoload.php')) {
        $sage_error(
            __('You must run <code>composer install</code> from the Sage directory.', 'sage'),
            __('Autoloader not found.', 'sage')
        );
    }
    require_once $composer;
}

/**
 * Sage required files
 *
 * The mapped array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 */
array_map(function ($file) use ($sage_error) {
    $file = "../app/{$file}.php";
    if (!locate_template($file, true, true)) {
        $sage_error(sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file), 'File not found');
    }
}, ['helpers', 'setup', 'filters', 'admin']);

/**
 * Here's what's happening with these hooks:
 * 1. WordPress initially detects theme in themes/sage/resources
 * 2. Upon activation, we tell WordPress that the theme is actually in themes/sage/resources/views
 * 3. When we call get_template_directory() or get_template_directory_uri(), we point it back to themes/sage/resources
 *
 * We do this so that the Template Hierarchy will look in themes/sage/resources/views for core WordPress themes
 * But functions.php, style.css, and index.php are all still located in themes/sage/resources
 *
 * This is not compatible with the WordPress Customizer theme preview prior to theme activation
 *
 * get_template_directory()   -> /srv/www/example.com/current/web/app/themes/sage/resources
 * get_stylesheet_directory() -> /srv/www/example.com/current/web/app/themes/sage/resources
 * locate_template()
 * ├── STYLESHEETPATH         -> /srv/www/example.com/current/web/app/themes/sage/resources/views
 * └── TEMPLATEPATH           -> /srv/www/example.com/current/web/app/themes/sage/resources
 */
array_map(
    'add_filter',
    ['theme_file_path', 'theme_file_uri', 'parent_theme_file_path', 'parent_theme_file_uri'],
    array_fill(0, 4, 'dirname')
);
Container::getInstance()
    ->bindIf('config', function () {
        return new Config([
            'assets' => require dirname(__DIR__).'/config/assets.php',
            'theme' => require dirname(__DIR__).'/config/theme.php',
            'view' => require dirname(__DIR__).'/config/view.php',
        ]);
    }, true);


/**
 * Walker Texas Ranger
 * Inserts some BEM naming sensibility into Wordpress menus
 */
class walker_texas_ranger extends Walker_Nav_Menu
{

    function __construct($css_class_prefix)
    {

        $this->css_class_prefix = $css_class_prefix;

        // Define menu item names appropriately

        $this->item_css_class_suffixes = [
            'item' => '__item',
            'parent_item' => '__item_parent',
            'active_item' => '__item',
            'parent_of_active_item' => '__item_parent-active',
            'ancestor_of_active_item' => '__item_ancestor-active',
            'sub_menu' => 'submenu',
            'sub_menu_item' => 'submenu__item',
            'link' => '__link',
        ];

    }

    // Check for children

    function display_element($element, &$children_elements, $max_depth, $depth = 0, $args, &$output)
    {

        $id_field = $this->db_fields['id'];

        if (is_object($args[0])) {
            $args[0]->has_children = !empty($children_elements[$element->$id_field]);
        }

        return parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);

    }

    function start_lvl(&$output, $depth = 1, $args = [])
    {

        $real_depth = $depth + 1;

        $indent = str_repeat("\t", $real_depth);

        $prefix = $this->css_class_prefix;
        $suffix = $this->item_css_class_suffixes;

        $classes = [
            $suffix['sub_menu'],
            $suffix['sub_menu'] . '--' . $real_depth
        ];

        $class_names = implode(' ', $classes);

        // Add a ul wrapper to sub nav

        $output .= "\n" . $indent . '<ul class="' . $class_names . '">' . "\n";
    }

    // Add main/sub classes to li's and links

    function start_el(&$output, $item, $depth = 0, $args = [], $id = 0)
    {

        global $wp_query;

        $indent = ($depth > 0 ? str_repeat("    ", $depth) : ''); // code indent

        $prefix = $this->css_class_prefix;
        $suffix = $this->item_css_class_suffixes;

        // Item classes
        $item_classes = [
            'item_class' => $depth == 0 ? $prefix . $suffix['item'] : '',
            'parent_class' => $args->has_children ? $parent_class = $prefix . $suffix['parent_item'] : '',
            'active_page_class' => in_array("current-menu-item",
                $item->classes) ? $prefix . $suffix['active_item'] : '',
            'active_parent_class' => in_array("current-menu-parent",
                $item->classes) ? $prefix . $suffix['parent_of_active_item'] : '',
            'active_ancestor_class' => in_array("current-menu-ancestor",
                $item->classes) ? $prefix . $suffix['ancestor_of_active_item'] : '',
            'depth_class' => $depth >= 1 ? $suffix['sub_menu_item'] . ' ' . $suffix['sub_menu'] . '--' . $depth . '__item' : '',
            'item_id_class' => $prefix . '__item--' . $item->object_id,
            'user_class' => $item->classes[0] !== '' ? $prefix . '__item--' . $item->classes[0] : ''
        ];

        // convert array to string excluding any empty values
        $class_string = implode(" ", array_filter($item_classes));

        // Add the classes to the wrapping <li>
        $output .= $indent . '<li class="' . $class_string . '">';

        // Link classes
        $link_classes = [
            'item_link' => $depth == 0 ? $prefix . $suffix['link'] : '',
            'depth_class' => $depth >= 1 ? $suffix['sub_menu'] . $suffix['link'] . ' ' . $suffix['sub_menu'] . '--' . $depth . $suffix['link'] : '',
        ];

        $link_class_string = implode("  ", array_filter($link_classes));
        $link_class_output = 'class="' . $link_class_string . '"';

        // link attributes
        $attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
        $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
        $attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';

        // Creatre link markup
        $item_output = $args->before;
        $item_output .= '<a' . $attributes . ' ' . $link_class_output . '>';
        $item_output .= $args->link_before;
        $item_output .= apply_filters('the_title', $item->title, $item->ID);
        $item_output .= $args->link_after;
        $item_output .= $args->after;
        $item_output .= '</a>';

        // Filter <li>

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

}

/**
 * bem_menu returns an instance of the walker_texas_ranger class with the following arguments
 * @param string $location This must be the same as what is set in wp-admin/settings/menus for menu location.
 * @param string $css_class_prefix This string will prefix all of the menu's classes, BEM syntax friendly
 * @param arr/string $css_class_modifiers Provide either a string or array of values to apply extra classes to the <ul> but not the <li's>
 * @return [type]
 */

function bem_menu($location = "main_menu", $css_class_prefix = 'main-menu', $css_class_modifiers = null)
{

    // Check to see if any css modifiers were supplied
    if ($css_class_modifiers) {

        if (is_array($css_class_modifiers)) {
            $modifiers = implode(" ", $css_class_modifiers);
        } elseif (is_string($css_class_modifiers)) {
            $modifiers = $css_class_modifiers;
        }

    } else {
        $modifiers = '';
    }

    $args = [
        'theme_location' => $location,
        'container' => false,
        'items_wrap' => '<ul class="' . $css_class_prefix . ' ' . $modifiers . '">%3$s</ul>',
        'walker' => new walker_texas_ranger($css_class_prefix, true)
    ];

    if (has_nav_menu($location)) {
        return wp_nav_menu($args);
    } else {
        echo "<p>You need to first define a menu in WP-admin<p>";
    }
}


