<?php
// $Id: signup.api.php,v 1.5 2009/09/19 01:42:52 dww Exp $


/**
 * @file
 * This file documents the hooks invoked by the Signup module.
 */

/**
 * Hook to define panes available to insert into the signup form.
 *
 * Panes should be provided by callback functions as a FormAPI array.
 * The callback should have the following signature:
 *   function my_callback(&$signup_form, &$form_state, $node, $signup, $pane_id, $signup_type = 'auth')
 * See signup_basic_form_form for an example.
 * The values submitted to the form elements defined by this form will be 
 * serialized and stored in the {signup_log} table as 'form_data'.
 *
 * @return
 *   An array of possible forms, keyed by a unique ID. Each value is itself an 
 *   array of data, with the following key-value pairs:
 *     - 'label': (required) The human-readable name of the form.
 *     - 'description': (required) Extra information about the form.
 *     - 'callback': (required) The name of a function.
 *     - 'operations': (optional) A list of links for the user to perform 
 *        administrative tasks on this pane, either global or per-node settings.
 *        The format is an unkeyed array of link arrays (suitable for passing
 *        to theme_links).
 *        You may use %nid as a token in your link's href property to
 *        insert the node id of the current signup node. 
 *        The following optional keys are also available:
 *        - destination: if set to TRUE, the return destination will be appended
 *          to the link as a query string with drupal_get_destination, allowing
 *          the user to return to this page directly. Do not use if your link
 *          sends the user to a complex series of forms or pages.
 *        - not_defaults: if set to TRUE, this link will not be shown when 
 *          default signup settings are being edited at admin/settings/signup.
 *          Use this when your settings link would be meaningless in this 
 *          context because it is dependent on the current node.
 *
 * @see signup_basic_form_form.
 */
function hook_signup_pane_info() {
  return array(
    'basic' => array(
      'label' => 'Basic form',
      'description' => 'Collects name and phone number.',
      'callback' => 'signup_basic_form_form',
    ),  
  );
}

/**
 * Hook to alter signup data before a signup is inserted or updated.
 *
 * @param $signup
 *   Reference to the fully-loaded signup object representing the signup.
 * @param $form_values
 *   Array of form values (if any) from the signup being inserted or updated.
 */
function hook_signup_data_alter(&$signup, $form_values) {
  // TODO
}

/**
 * Hook invoked when a signup is being canceled.
 *
 * At the time this hook is invoked the record about the signup in the
 * {signup_log} table still exists, but the node has already had its signup
 * total decremented.
 *
 * @param $node
 *   The fully-loaded node object that the signup is being canceled from.
 * @param $signup
 *   An object containing all the known information about the signup being
 *   canceled. Contains all the data from the {signup_log} row representing
 *   the canceled signup. See the schema definition for descriptions of each
 *   field and what they represent.
 *
 * @return
 *   Ignored.
 *
 * @see signup_cancel_signup()
 */
function hook_signup_cancel($signup, $node) {
  $info = array();
  $info[] = t('Signup ID: @sid', array('@sid' => $signup->sid));
  $info[] = t('Node ID: @nid', array('@nid' => $signup->nid));
  $info[] = t('User ID: @uid', array('@uid' => $signup->uid));
  $info[] = t('Email address for anonymous signup: @anon_mail', array('@anon_mail' => $signup->anon_mail));
  $info[] = t('Date/time when the signup was created: @signup_time', array('@signup_time' => $signup->signup_time));
  $form_data = unserialize($signup->form_data);
  $info[] = t('Custom signup form data: %signup_form_data', array('%signup_form_data' => theme('signup_custom_data_email', $form_data)));
  $info[] = t('Attendance record: %attended', array('%attended' => theme('signup_attended_text', $signup->attended)));
  $info[] = t('Slots consumed by this signup: @count_towards_limit', array('@co
unt_towards_limit' => $signup->count_towards_limit));

  drupal_set_message(theme('item_list', $info, t('Signup canceled for %node_title', array('%node_title' => $node->title))));
}

/**
 * Hook invoked after a signup has been inserted.
 *
 * @param $signup
 *   The fully-loaded signup object representing the new signup.
 */
function hook_signup_insert($signup) {
  // TODO
}

/**
 * Hook invoked after a signup has been updated.
 *
 * @param $signup
 *   The fully-loaded signup object representing the updated signup.
 */
function hook_signup_update($signup) {
  // TODO
}

/**
 * Hook invoked when a signup is being created to gather other signup data.
 *
 * This hook allows other modules to inject information into the custom signup
 * data for each signup.  The array is merged with the values of any custom
 * fields from hook_signup_pane_info(), serialized, and stored in the
 * {signup_log} database table.
 *
 * @param $node
 *   Fully-loaded node object being signed up to.
 * @param $account
 *   Full-loaded user object who is signing up.
 *
 * @return
 *   Keyed array of fields to include in the custom data for this signup. The
 *   keys for the array are used as labels when displaying the field, so they
 *   should be human-readable (and wrapped in t() to allow translation).
 *
 * @see signup_sign_up_user()
 * @see hook_signup_pane_info()
 */
function hook_signup_sign_up($node, $account) {
  return array(
    t('Node type') => node_get_types('name', $node->type),
    t('User created') => format_date($account->created),
  );
}


/**
 * Hook invoked whenever a node is reopened for signups.
 *
 * A node with signups closed could be reopened in two main cases: 1) someone
 * cancels a signup and the signup limit is no longer reached; 2) a signup
 * administrator manually re-opens signups.
 *
 * @param $node
 *   Fully-loaded node object that is now open for signups.
 *
 * @return
 *   Ignored.
 *
 * @see signup_open_signup()
 */
function hook_signup_open($node) {
  drupal_set_message(t('Duplicate message: signups are now open on %title.', array('%title' => $node->title)));
}


/**
 * Hook invoked whenever a node is closed for signups.
 *
 * Signups are closed in 3 main cases: 1) it is a time-based node and the
 * close-in-advance time has been reached (auto-close via cron); 2) the node
 * has a signup limit and the limit is reached; 3) a signup administrator
 * manually closes signups.
 *
 * @param $node
 *   Fully-loaded node object that is now closed for signups.
 *
 * @return
 *   Ignored.
 *
 * @see signup_close_signup()
 */
function hook_signup_close($node) {
  drupal_set_message(t('Duplicate message: signups are now closed on %title.', array('%title' => $node->title)));
}


/**
 * Hook invoked to see if signup information should be printed for a node.
 *
 * This hook is invoked whenever someone is viewing a signup-enabled node and
 * allows modules to suppress any signup-related output.  If any module's
 * implementation of this hook returns TRUE, no signup information will be
 * printed for that node.
 *
 * @param $node
 *   The fully-loaded node object being viewed.
 *
 * @return
 *   TRUE if you want to prevent signup information from being printed, FALSE
 *   or NULL if the information should be printed.
 *
 * @see _signup_needs_output()
 * @see _signup_menu_access()
 * @see signup_nodeapi()
 */
function hook_signup_suppress($node) {
  if ($node->nid % 2) {
    drupal_set_message(t('Signup information suppressed for odd node ID %nid.', array('%nid' => $node->nid)));
    return TRUE;
  }
}

/**
 * Hook invoked to control access to signup menu items.
 *
 * This hook is invoked to check access for signup menu items, in particular,
 * the signup-related tabs on signup-enabled nodes. If no value is returned
 * (NULL), the hook is ignored and the usual access logic is enforced via the
 * Signup module. If multiple modules return a value, the logical OR is used,
 * so if anyone returns TRUE, access is granted. If everyone returns FALSE,
 * access is denied (even if the Signup module would normally grant access).
 *
 * @param $node
 *   The fully-loaded node object where the menu items would be attached.
 * @param $menu_type
 *   String specifying what kind of menu item to test access for. Can be:
 *   'signup': the signup form
 *   'list': the signup attendee listing
 *   'admin': the signup administration tab
 *   'add': the signup administration tab to add other users (requires
 *          that signups are currently open on the given node).
 *   'broadcast': for the broadcast tab
 *
 * @return
 *   TRUE if you want to allow access to the requested menu item, FALSE if you
 *   want to deny access (although if another hook implementation returns
 *   TRUE, that will take precedence), or NULL if you don't care and want to
 *   let Signup module itself decide access based on its own logic.
 *
 * @see _signup_menu_access()
 */
function hook_signup_menu_access($node, $menu_type) {
  // For example, you might want to test that the current user is the
  // administrator of the organic group that a signup-enabled node belongs to,
  // in which case, you'd return TRUE here to give that user full signup
  // powers over events in their group.
}

/**
 * Implementation of hook_signup_form_data_display().
 *
 * Allow modules to alter signup form data prior to displaying signup records
 * in, for example, a node's list of signups at node/N/signups/admin.
 *
 * This allows modules that implement signup panes for format or even inject 
 * their own data.
 *
 * @param $form_data
 *  The user's signup data to alter.
 * @param $nid
 *  The node id for the signup-enabled node.
 * @param $sid
 *  The signup record id.
 * @param $uid
 *  The user id whose signup this is; 0 if this is an anonymous signup.
 * @param $type
 *  The type of output being prepared. Possible values are:
 *    - 'list': The hardcoded admin lists of signups, eg at node/X/signups/admin
 *    - 'view': The form data field in Views.
 *    - 'mail': Email output. This is likely the only one that needs special 
 *      handling; in this case, modules should be more generous about supplying
 *      data since there's no other place to see it.
 */
function hook_signup_form_data_display_alter(&$form_data, $nid, $sid, $uid, $type = 'list') {
  foreach ($form_data as $pane_id => $pane_data) {
    // If this is one of your panes, do stuff to it.
  }
}
