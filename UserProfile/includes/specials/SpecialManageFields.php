<?php
/**
 * A special page for managing glabal state of profiles fields
 *
 * @file
 * @ingroup Extensions
 * @author WikiTeq
 * @copyright Copyright © 2021, WikiTeq
 * @license GPL-2.0-or-later
 */

class SpecialManageFields extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ManageFields', 'manage-profiles-fields' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $params
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, they don't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policy, etc.
		$this->setHeaders();

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$fieldsToDisable = $this->getRequest()->getArray('fields', []);
			UserProfile::resetDisabledFields();
			if( count( $fieldsToDisable ) ) {
				$fieldsToDisable = array_keys( $fieldsToDisable );
				foreach ( $fieldsToDisable as $fieldToDisable ) {
					UserProfile::disableField( $fieldToDisable );
				}
			}
			$this->getOutput()->addWikiMsg( 'managefields-saved' );
		}

		$out->addHTML( $this->displayForm() );

	}

	/**
	 * Render the confirmation form
	 *
	 * @return string HTML
	 */
	private function displayForm() {
		$form = '<form method="post" name="manage-profiles-fields" action="">';
		$form .= '<p>' . $this->msg( 'managefields-desc' )->escaped() . '</p>';
		$form .= '<br />';

		$fields = UserProfile::getFields();
		$disabledFields = UserProfile::getDisabledFields();

		foreach ( $fields as $field => $msg ) {
			$form .= Html::rawElement(
				'div',
				[],
				Html::check( 'fields['.$field.']', in_array( $field, $disabledFields ), [ 'id' => $field ] ) .
				Html::label( $this->msg( $msg ), $field )
			);
		}

		$form .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );
		// passing null as the 1st argument makes the button use the browser default text
		// (on Firefox 72 with English localization this is "Submit Query" which is good enough,
		// since MW core lacks a generic "submit" message and I don't feel like introducing
		// a new i18n msg just for this button...)
		$form .= Html::submitButton( null, [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}
}
