<?php
/**
 * MslsCustomColumnTaxonomy
 * @author Dennis Ploetner <re@lloc.de>
 * @since 0.9.8
 */

/**
 * Handling of existing/not existing translations in the backend 
 * listings of various taxonomies
 * @package Msls
 */
class MslsCustomColumnTaxonomy extends MslsCustomColumn {

	/**
	 * Init
	 * @return MslsCustomColumnTaxonomy
	 */
	static function init() {
		$obj = new self();
		$options = MslsOptions::instance();
		if ( !$options->is_excluded() ) {
			$taxonomy = MslsTaxonomy::instance()->get_request();
			if ( !empty( $taxonomy ) ) {
				add_filter( "manage_edit-{$taxonomy}_columns" , array( $obj, 'th' ) );
				add_action( "manage_{$taxonomy}_custom_column" , array( $obj, 'td' ), 10, 3 );
				add_action( "delete_{$taxonomy}", array( $obj, 'delete' ), 10, 2 );
			}
		}
		return $obj;
	}

	/**
	 * Table body
	 * @param string $deprecated
	 * @param string $column_name
	 * @param int $item_id
	 */
	public function td( $deprecated, $column_name, $item_id ) {
		parent::td( $column_name, $item_id );
	}

	/**
	 * Delete
	 * @param int $term_id
	 * @param int $tt_id
	 */
	public function delete( $term_id, $tt_id ) {
		$options = new MslsOptionsTax( $term_id );
		$this->save( $term_id, 'MslsOptionsTax', $options->get_arr() );
	}

}
