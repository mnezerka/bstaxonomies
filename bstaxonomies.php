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
        add_action('admin_init', array($this, 'onAdminInit'));

        // Register our bstaxonomies to the admin_menu action hook.
        add_action('admin_menu', array($this, 'onAdminMenu'));

        add_filter('upload_mimes', array($this, 'onUploadMimeTypes'), 1, 1);
        add_action('wp_enqueue_scripts', array($this, 'onEnqueueScripts'));
    }

    public function onEnqueueScripts() {

        // leaflet
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.js');

        // leaflet full screen control - https://github.com/brunob/leaflet.fullscreen
        wp_enqueue_style('leaflet-fullscreen-css', plugins_url('/js/leaflet.fullscreen-1.6.0/Control.FullScreen.css', __FILE__));
        wp_enqueue_script('leaflet-fullscreen-js', plugins_url('/js/leaflet.fullscreen-1.6.0/Control.FullScreen.js', __FILE__), array('leaflet-js'));

        // needs to be inserted into the footer, it needs to see DOM element for rendering
        wp_enqueue_script('bstaxonomies-map-js', plugins_url('/js/map.js', __FILE__), array('leaflet-js'), null, 1);
        wp_enqueue_style('bstaxonomies-css', plugins_url('/css/bstaxonomies.css',__FILE__ ));
    }

    public function onInit()
    {
        // Register new shortcode
        add_shortcode('bstaxonomies', array($this, 'bstaxonomies_shortcode'));
    }

    public function onAdminInit()
    {
        // Register a new section in the "bstaxonomies" page.
        add_settings_section(
            // ID used to identify this section
            'bstaxonomies_section',
            // Title to be displayed on the administration page
            '',
            // Callback used to render the description of the section
            array($this, 'renderOptionsPageDescription'),
            // Page on which to add this section of options
            'bstaxonomies'
        );

        // Register a new field in the "bstaxonomies_section" section, inside the "bstaxonomies" page.
        add_settings_field(
            'bstaxonomies_loc', // As of WP 4.6 this value is used only internally.
                                // Use $args' label_for to populate the id inside the callback.
            'Locations',
            array($this, 'renderOptionsFieldLoc'),
            'bstaxonomies',
            'bstaxonomies_section',
            array(
                'type' => 'input',
                'subtype' => 'text',
                'id' => 'bstaxonomies_loc',
                'name' => 'bstaxonomies_loc',
                'value_type' => 'normal',
                'wp_data' => 'option',
                'label_for' => 'bstaxonomies_loc',
                'class' => 'bstaxonomies_row',
                'bstaxonomies_loc' => 'custom',
            )
        );

        // Register a new setting for "bstaxonomies" page.
        register_setting('bstaxonomies', 'bstaxonomies_loc');
    }

    /**
     * Add the top level menu page.
     */
    public function onAdminMenu() {

        add_posts_page(
            'Tag Locations',
            'Tag Locations',
            'manage_options',
            'bstaxonomies',
            array($this, 'renderOptionsPage')
        );
    }

    /**
    * Implementation of "bstaxonomies" shortcode
    *
    * @param array $attr Attributes of the shortcode.
    * @return string HTML content to be sent to browser
    */
    public function bstaxonomies_shortcode($atts = [])
    {
        //These are all the 'options' you can pass in through the
        // shortcode definition, eg: [bstaxonomies type='category']
        extract(shortcode_atts(array(
            'type'      => 'tags'
        ), $atts));


        $output = '';

        switch($type) {
           case "tags":
                $tags = get_tags();
                $letterTags = [];

                // transofrm tags to dict of dicts, whwere key is first letter + tag name
                foreach ($tags as $tag) {
                    // get first character (be careful on unicode, we need mb_* function)
                    $firstLetter = strtoupper(mb_substr($tag->name, 0, 1));

                    // create new letter dictionary if doesn't exist yet
                    if (!array_key_exists($firstLetter, $letterTags)) {
                        $letterTags[$firstLetter] = [];
                    }

                    $tag_link = get_tag_link($tag->term_id);
                    $letterTags[$firstLetter][$tag->name] = '<a href="' . $tag_link . '" title="' . $tag->name . '">' . $tag->name . ' (' . $tag->count . ')</a>';
                }

                // sort list by first letter (main key of the list)
                ksort($letterTags);

                foreach ($letterTags as $letter => $tags) {

                    $output .= '<p><b>' . $letter. '</b></p>';

                    // sort tags by name
                    ksort($tags);

                    $output .= '<p>' . implode(', ', $tags) . '</p>';
                }

                break;

           case "tagsmap":

                // element to which map is rendered
                $output .= '<div id="bstaxonomies-map" style="width: 100%; height: 400px;"></div>';

                $tags = $this->_getLocalizedTags();

                // this is way how Wordpres supports passing php parameters to javascript
                $jsData = array(
                    'tags' => $tags,
                );

                wp_localize_script('bstaxonomies-map-js', 'params', $jsData);

                break;

           case "categories":
                $output .= '<ul class="bstaxonomies tags-list">';
                $categories = get_categories();
                foreach ($categories as $category) {
                    $category_link = get_category_link($category->term_id);
                    $output .= '<a href="' . $category_link . '" title="' . $category->name . '">' . $category->name . ' (' . $category->count . ')</a>';
                }

                $output .= '</ul>';
                break;
        }

        return $output;
    }

    /**
     * Developers section callback function.
     *
     * @param array $args  The settings array, defining title, id, callback.
     */
    public function renderOptionsPageDescription($args) {
        echo '<p>Settings for BSTaxonomies</p>';
    }

    /**
     * Render form field
     *
     * @param array $args
     */
    function renderOptionsFieldLoc($args) {

        // Get the value of the setting we've registered with register_setting()
        $locs = get_option('bstaxonomies_loc');

        echo '<p>';
        echo '<textarea id="bstaxonomies_loc" name="bstaxonomies_loc" rows="30" cols="50" type="textarea">' .  $locs .  '</textarea>';
        echo '</p>';

        $locsParsed = $this->_parseTagLocations($locs);

        // try to match parsed locs with real wp tags
        $tagsWithoutLoc = [];

        $tags = get_tags();
        foreach ($tags as $tag) {
            if (!array_key_exists($tag->name, $locsParsed)) {
                $tagsWithoutLoc[] = $tag->name;
            }
        }

        // render list of tags without location (if such tag exists)
        if (count($tagsWithoutLoc) > 0) {
            sort($tags);
            echo '<p><i>Format of line: tag; lat; lon</i></p>';
            echo '<p>';
            echo "Tags without location: " . implode(', ', $tagsWithoutLoc);
            echo '</p>';
        }
    }

    /**
     * Top level menu callback function
     */
    public function renderOptionsPage() {

        // check user capabilities
        if (! current_user_can('manage_options')) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        if (isset( $_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('bstaxonomies_messages', 'bstaxonomies_message', __('Settings Saved', 'wporg'), 'updated');
        }

        // show error/update messages
        settings_errors('bstaxonomies_messages');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "bstaxonomies"
                settings_fields('bstaxonomies');

                // output setting sections and their fields
                // (sections are registered for "bstaxonomies", each field is registered to a specific section)
                do_settings_sections('bstaxonomies');

                // output save settings button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    private function _getLocalizedTags() {
        $result = [];

        // Get the value of the setting we've registered with register_setting()
        $locs = get_option('bstaxonomies_loc');
        $tagLocs = $this->_parseTagLocations($locs);

        // loop through all wp tags
        $tags = get_tags();
        foreach ($tags as $tag) {

            // if we have location for current tag
            if (array_key_exists($tag->name, $tagLocs)) {
                $tag_link = get_tag_link($tag->term_id);
                $result[] = [
                    'loc' => $tagLocs[$tag->name],
                    'link' => '<a href="' . $tag_link . '" title="' . $tag->name . '">' . $tag->name . ' (' . $tag->count . ')</a>',
                    'tag' => $tag->name
                ];
            }
        }
        return $result;
    }

    private function _parseTagLocations($str) {

        $result = [];

        $lines = explode("\n", $str);

        foreach ($lines as $line) {
            $parts = explode(";", $line);

            // ignore wrongly formatted lines
            if (count($parts) != 3) {
                continue;
            }

            // check proper formatting of tag name
            if (mb_strlen($parts[0]) == 0) {
                continue;
            }

            // check proper formatting of lat and lng
            $parts[1] = (float)$parts[1];
            $parts[2] = (float)$parts[2];
            if ($parts[1] == 0 || $parts[2] == 0) {
                continue;
            }

            $result[$parts[0]] = [$parts[1], $parts[2]];
        }

        return $result;
    }
}

// create plugin instance
$bsMaps = new BSTaxonomies();
?>
