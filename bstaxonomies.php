<?php
/*
vim: set expandtab sw=4 ts=4 sts=4 foldmethod=indent:
Plugin Name: BSTaxonomies
Description: Wordpress plugin for rendering list of taxonomies
Version: 1.0
Author: Michal Nezerka
Author URI: http://blue.pavoucek.cz
Text Domain: bstaxonomies
Domain Path: /languages
*/

/*
 * Implementation of BSTaxonomies plugin
 */
class BSTaxonomies
{
    public function __construct()
    {
        add_action('init', array($this, 'onInit'));
    }

    public function onInit()
    {
        // Register new shortcode
        add_shortcode('bstaxonomies', array($this, 'bstaxonomies_shortcode'));
    }

    /**
    * Implementation of "bstaxonomies" shortcode
    *
    * @param array $attr Attributes of the shortcode.
    * @return string HTML content to be sent to browser
    */
    public function bstaxonomies_shortcode($atts = [])
    {
        //var_dump($attr);

        //These are all the 'options' you can pass in through the
        // shortcode definition, eg: [bstaxonomies type='category']
        extract(shortcode_atts(array(
            'type'      => 'tags'
        ), $atts));


        $output = '';

        switch($type) {
           case "tags":
                $output .= '<ul class="bstaxonomies tags-list">';
                $tags = get_tags();
                foreach ($tags as $tag) {
                    $tag_link = get_tag_link($tag->term_id);
                    $output .= '<li>';
                    $output .= '<a href="' . $tag_link . '" title="' . $tag->name . '">' . $tag->name . ' (' . $tag->count . ')</a>';
                    $output .= '</li>';
                }

                $output .= '</ul>';
                break;

            case "categories":
                $output .= '<ul class="bstaxonomies tags-list">';
                $categories = get_categories();
                foreach ($categories as $category) {
                    $category_link = get_category_link($category->term_id);
                    $output .= '<li>';
                    $output .= '<a href="' . $category_link . '" title="' . $category->name . '">' . $category->name . ' (' . $category->count . ')</a>';
                    $output .= '</li>';
                }

                $output .= '</ul>';
                break;
        }

        return $output;
    }
}

// create plugin instance
$bsMaps = new BSTaxonomies();
?>
