
# WordPress-Notes-Cheat-Sheet
I hate googling through WordPress documentation and trying out some solutions offered by StackOverflow. Instead of doing those processes all over again, might as well document WordPress-y stuff that I find useful for me.


## Wordpress Plugin Boilerplate
Use this resource to create a new WordPress Plugin
- [WPBB](https://wppb.me/)

## Custom Post Types
My personal best practice is to create a models folder inside either the admin or includes folder

    admin
    |
    |--models
    |   |-- Example.php
    |
Inside Example.php
```php
<?php
class Example 
{
    
}
```

### Create custom post type
```php
public function create_example_posttype()
{
    $labels = array(
        'name'                  => __( 'Examples' ),
        'singular_name'         => __( 'Example' ),
        'add_new'               => __( 'Add New Example' ),
        'add_new_item'          => __( 'Add New Example' ),
        'edit_item'             => __( 'Edit Example' ),
        'new_item'              => __( 'Add New Example' ),
        'view_item'             => __( 'View Example' ),
        'search_items'          => __( 'Search Example' ),
        'not_found'             => __( 'No examples found' ),
        'not_found_in_trash'    => __( 'No examples found in trash' )

    );
    
    $supports = array(
        'title', // I usually need the title
    );
    
    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'has_archive'           => true,
        'rewrite'               => array('slug' => 'example'),
        'show_in_rest'          => true,
        'supports'              => $supports,
        
        // below if you want to have a meta box
        'register_meta_box_cb'  => array( $this, 'add_example_metaboxes' ),  

    );

    register_post_type( 'example', $args);
}
```

### Create the Meta Box
```php	
public  function  add_example_metaboxes()
{
    add_meta_box(
        'infinite189_examples_id',
        'example',
        
        // use $this to call function from the same class
        [$this, 'examples_meta_box'], 
        'examples',
        'normal',
        'default'
    );
}

public function examples_meta_box($post)
{
    // initialise variables
    $example = (object) get_post($post->ID);
    $field   = esc_textarea($example->field);
    
    // required view file
    require_once  plugin_dir_path( __FILE__ ) .  '../partials/the-view.php';
}
```

### Saving the Metas
```php
public function save_examples_meta()
{
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return  $post_id;
    }
    
    $meta = [
        'field'		=> esc_textarea($_POST['field'],
    ]
    
    // magic
    foreach ( $schedule_meta as $key => $value ) :
        // Don't store custom data twice
        if ( 'revision' === $post->post_type ) {
            return;
        }

        if ( get_post_meta( $post_id, $key, false ) ) {
            // If the custom field already has a value, update it.
            update_post_meta( $post_id, $key, $value );
        } else {
            // If the custom field doesn't have a value, add it.
            add_post_meta( $post_id, $key, $value);
        }

        if ( ! $value ) {
            // Delete the meta key if there's no value
            delete_post_meta( $post_id, $key );
        }

    endforeach;
}
```

### Setting the columns in all CPTs table
```php
public function set_examples_columns($columns)
{
    unset($columns['date']); // to remove the date column
    $columns['field'] = __('Field'); // to add a field
    
    return $columns; 
}
```

### Setting the value of each column
```php
public function set_examples_column($column, $post_id)
{
    // you might need to query the CPT
    $example = (object) get_post($post_id);
    $field   = $example->field;
    
    switch($column) {
        case 'field':
        echo $field;
        break;
    }
}
```

## Hooking Everything Together
In `plugin-name/includes/class-plugin-name.php`

### Loading dependencies
```php
private function load_dependencies()
{
    require_once  plugin_dir_path( dirname( __FILE__ ) ) .  'admin/models/Example.php';
}
```

### Hooking to admin hooks
```php
private function define_admin_hooks()
{
    $example_model = new Example();

    // $loader is a private variable which loads the action, if that makes sense
    $this->loader->add_action( 'init', $example_model , 'create_example_posttype' );
    $this->loader->add_action( 'save_post', $example_model , 'save_examples_meta', 1, 2 );
    $this->loader->add_action( 'manage_examples_posts_columns', $example_model , 'set_examples_columns' );
    $this->loader->add_action( 'manage_examples_posts_custom_column', $example_model , 'set_examples_column', 10, 2 );
}
```

## Web Service for AJAX
In your service class (i.e., ExampleService)

```php
class ExampleService
{
    public function foo_action()
    {
        Request::validate_post_request();

        Request::validate_nonce();

        $response = ['success' => true, 'message' => 'foo bar', 'data' => ['fizz' => 'buzz']];

        wp_send_json($response, 200);
    }
}
```

In your `class-plugin-name.php`

```php
class PluginName
{
    // don't forget to load Example Service in load_dependecies()

    private function define_admin_hooks()
    {
        $example_service = new ExampleService();

        // /wp-admin/admin-ajax.php?action=foo_action
        $this->loader->add_action( 'wp_ajax_foo_action', $example_service, 'foo_action');
    }
}
```

## Nonce.
The way I think of Nonce is like CSRF token. In the `Utils` folder, you can see there is `Request.php` class file. You can call `Request::get_nonce()` to get the nonce token. Always pass your nonce token to key parameter nonce. See the examples below

### Jquery
```html
<input type='hidden' id='some_nonce' name='nonce' value="<?= Request::get_nonce(); ?>"/>
```

```javascript
    $(function() {
        $("#some_id").on('click', function() {
            const nonce = $("#some_nonce").val();
            const data = { nonce: nonce, key: value }
            const url = ``;
            $.post(url, data, function() {
                // do something
                // i hate JQuery
            }, 'json');
        });
    })
```

### Vue
```javascript
var app = new Vue({
    data: {
        form: {
            action: 'some_action', // must be available
            nonce: '',
            name: 'foo',
            bar: 'baz',
        }
    },
    methods: {
        async loadData() {
            const response = await axios.get('');
            const { data } = response;
            const { something, nonce } = data; // getting the nonce from your web service
            this.form.nonce = nonce // assuming you have data form 
        },

        // for you to get the FormData object, since axios works very weird with
        // api created by wordpress when sending form data.
        // If you use axios to send AJAX request, to POST form data to wordpress API
        // Wordpress will return 0 for some weird reason. This is the work around. 
        // Don't waste your time ever again
        getFormData() {
            let form_data = new FormData();

            for ( let key in this.form ) {
                form_data.append(key, this.form[key]);
            }

            return form_data;
        },

        async postData() {
            // getting the base url of the current website
            const base_url = window.location.origin;

            // always send to below link for WP Admin AJAX request
            const link = `${base_url}/wp-admin/admin-ajax.php`;

            const response = await axios.post(link, this.getFormData());
            const { data } = response;

            // do something here
        }
    }
})
```

## Misc.

### Adding Shortcodes
In `plugin-name/includes/class-plugin-name-loader.php`
```php
class Plugin_Name_Loader()
{
    private $shortcodes;

    public function __construct()
    {
        $this->shortcodes() = array();
    }

    public  function  add_shortcode( $tag, $component, $callback, $priority = 10, $accepted_args = 2 ) {

        $this->shortcodes = $this->add( $this->shortcodes, $tag, $component, $callback, $priority, $accepted_args );
    }

    public function run()
    {
        foreach ( $this->shortcodes as $hook ) {
            add_shortcode( $hook['hook'], array( $hook['component'], $hook['callback'] ));
        }
    }
}
```

Then in `plugin-name/includes/class-plugin-name.php`
```php
public function define_public_hooks()
{
    // to ensure shortcodes only running in non admin page
    if (!is_admin()) {
        $this->loader->add_shortcode('example_view', $example_service, 'example_view');
    }
}
```
