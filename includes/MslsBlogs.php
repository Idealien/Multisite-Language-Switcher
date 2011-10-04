<?php

/**
 * Blogs
 *
 * @package Msls
 */

/**
 * MslsBlogCollection implements IMslsRegistryInstance
 */
require_once dirname( __FILE__ ) . '/MslsRegistry.php';

/**
 * MslsBlogCollection uses get_user_id_from_string()
 */
require_once ABSPATH . WPINC . '/ms-functions.php';

/**
 * Collection of blog-objects
 * 
 * Implements the interface IMslsRegistryInstance because we are working with an
 * single object of MslsBlogCollection all the time.
 * @package Msls
 */
class MslsBlogCollection implements IMslsRegistryInstance {

    /**
     * @var int ID of the current blog
     */
    private $current_blog_id;

    /**
     * @var bool True if the current blog should be in the output
     */
    private $current_blog_output;

    /**
     * @var array Collection of MslsBlog-objects
     */
    private $objects = array();

    /**
     * @var string Order output by language or description
     */
    private $objects_order;

    /**
     * Constructor
     */
    public function __construct() {
        $options                   = MslsOptions::instance();
        $this->current_blog_id     = get_current_blog_id();
        $this->current_blog_output = $options->has_value( 'output_current_blog' );
        $this->objects_order       = $options->get_order();
        if ( !$options->is_excluded() ) {
            if ( has_filter( 'msls_blog_collection_construct' ) ) {
                $blogs_collection = apply_filters(
                    'msls_blog_collection_construct',
                    array()
                );
            }
            else {
                $user_id = get_user_id_from_string(
                    get_blog_option( $this->current_blog_id, 'admin_email' )
                );
                $blogs_collection = get_blogs_of_user( $user_id );
            }
            foreach ( (array) $blogs_collection as $blog ) {
                if ( $blog->userblog_id != $this->current_blog_id ) {
                    $temp = get_blog_option( $blog->userblog_id, 'msls' );
                    if ( is_array( $temp ) && empty( $temp['exclude_current_blog'] ) ) {
                        $this->objects[$blog->userblog_id] = new MslsBlog(
                            $blog,
                            $temp['description']
                        );
                    }
                }
                else {
                    $this->objects[$this->current_blog_id] = new MslsBlog(
                        $blog,
                        $options->description
                    );
                }
            }
        }
    }

    /**
     * Get the id of the current blog
     *
     * @return int ID of the current blog
     */
    public function get_current_blog_id() {
        return $this->current_blog_id;
    }

    /**
     * Check if current blog is in the collection
     *
     * @return bool Is the current blog part of the output? 
     */
    public function has_current_blog() {
        return(
            isset( $this->objects[$this->current_blog_id] ) ?
            true :
            false
        );
    }

    /**
     * Get current blog as object
     *
     * @return MslsBlog|null Current blog as MslsBlog-Object
     */
    public function get_current_blog() {
        return(
            $this->has_current_blog() ?
            $this->objects[$this->current_blog_id] :
            null
        );
    }

    /**
     * Get an array with blog-objects
     *
     * @param bool $frontend Are we in the frontend?
     * @return array Collection of MslsBlog-objects
     */
    public function get( $frontend = false ) {
        $objects = apply_filters( 'msls_blog_collection_get', $this->objects );
        if ( (!$frontend || !$this->current_blog_output) && $this->has_current_blog() )
            unset( $objects[$this->current_blog_id] );
        usort( $objects, array( 'MslsBlog', $this->objects_order ) );
        return $objects;
    }

    /**
     * Get or create a instance of MslsBlogCollection
     *
     * @return MslsBlogCollection
     */
    public static function instance() {
        $registry = MslsRegistry::singleton();
        $cls      = __CLASS__;
        $obj      = $registry->get_object( $cls );
        if ( is_null( $obj ) ) {
            $obj = new $cls;
            $registry->set_object( $cls, $obj );
        }
        return $obj;
    }

}

/**
 * Internal representation of a blog
 *
 * @package Msls
 */
class MslsBlog {

    /**
     * @var StdClass WordPress generates such an object
     */
    private $obj;

    /**
     * @var string Description eg. Deutsch
     */
    private $description;

    /**
     * @var string Language-code eg. de_DE
     */
    private $language;

    /**
     * Constructor
     *
     * @param StdClass $obj 
     * @param string description
     */
    public function __construct( StdClass $obj, $description ) {
        /*
         * get_user_id_from_string returns objects with userblog_id-members 
         * instead of a blog_id ... so we need just some correction ;)
         *
         */
        if ( !isset( $obj->userblog_id ) ) {
            $obj->userblog_id = $obj->blog_id;
        }
        $this->obj         = $obj;
        $this->description = (string) $description;
        $this->language    = (string) get_blog_option( $this->obj->userblog_id, 'WPLANG' );
    }

    /**
     * Get a member of the StdClass-object by name
     *
     * The method return <em>null</em> if the requested member does not exists.
     * 
     * @param string $key
     * @return mixed|null
     */
    final public function __get( $key ) {
        return(
            isset( $this->obj->$key ) ?
            $this->obj->$key :
            null
        );
    }

    /**
     * Get the description stored in this object
     * 
     * The method returns the stored language if the description is empty.
     * 
     * @return string
     */
    public function get_description() {
        return(
            !empty( $this->description ) ?
            $this->description :
            $this->get_language()
        );
    }

    /**
     * Get the language stored in this object
     * 
     * The method returns the string 'us' if there is an empty value in language.  
     * 
     * @return string
     */
    public function get_language() {
        return(
            !empty( $this->language ) ?
            $this->language :
            'us'
        );
    }

    /**
     * Sort objects helper
     * 
     * @param mixed $a
     * @param mixed $b
     * return int
     */
    public static function _cmp( $a, $b ) {
        if ( $a == $b ) {
            return 0;
        }
        return( $a < $b ? (-1) : 1 );
    }

    
    /**
     * Sort objects by language
     * 
     * @param mixed $a
     * @param mixed $b
     * return int
     */
    public static function language( $a, $b ) {
        return( self::_cmp( $a->get_language(), $b->get_language() ) );
    }

    /**
     * Sort objects by description
     * 
     * @param mixed $a
     * @param mixed $b
     * return int
     */
    public static function description( $a, $b ) {
        return( self::_cmp( $a->get_description(), $b->get_description() ) );
    }

}

?>
