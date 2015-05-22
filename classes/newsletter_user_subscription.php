<?php
/**
 * class used by newsletter subscriber selection controls
 * @package mod-newsletter
 * @copyright 2015 David Bogner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/user/selector/lib.php');

class mod_newsletter_potential_subscribers extends user_selector_base {
	protected $newsletterid;
	
	public function __construct($name, $options) {
		$this->newsletterid  = $options['newsletterid'];
		parent::__construct($name, $options);
	}
	
	/**
	 * 
	 * @return array
	 */
	protected function get_options() {
		$options = parent::get_options();
		$options['file'] = 'mod/newsletter/classes/newsletter_user_subscription.php';
		$options['newsletterid'] = $this->newsletterid;
		$options['potentialusers'] = $this->potentialusers;
		// Add our custom options to the $options array.
		return $options;
	}

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['newsletterid'] = $this->newsletterid;

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
            LEFT JOIN {newsletter_subscriptions} ns ON (ns.userid = u.id AND ns.newsletterid = :newsletterid)
                WHERE $wherecondition
                      AND ns.id IS NULL";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }


        if ($search) {
            $groupname = get_string('subscribercandidatesmatching', 'newsletter', $search);
        } else {
            $groupname = get_string('subscribercandidates', 'newsletter');
        }
        return array($groupname => $availableusers);
    }
}

/**
 * Subscribed users.
 */
class mod_newsletter_existing_subscribers extends user_selector_base {
	protected $courseid;
	protected $newsletterid;

	public function __construct($name, $options) {
		$this->newsletterid  = $options['newsletterid'];
		parent::__construct($name, $options);
	}

	/**
	 * Candidate users
	 * @param string $search
	 * @return array
	 */
	public function find_users($search) {
		global $DB;
		// By default wherecondition retrieves all users except the deleted, not confirmed and guest.
		list($wherecondition, $params) = $this->search_sql($search, 'u');
		$params['newsletterid'] = $this->newsletterid;

		$fields      = 'SELECT ' . $this->required_fields_sql('u');
		$countfields = 'SELECT COUNT(1)';

		$sql = " FROM {user} u
		JOIN {newsletter_subscriptions} ns ON (ns.userid = u.id AND ns.newsletterid = :newsletterid)
		WHERE $wherecondition";

		list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
		$order = ' ORDER BY ' . $sort;

		if (!$this->is_validating()) {
			$potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
			if ($potentialmemberscount > $this->maxusersperpage) {
				return $this->too_many_results($search, $potentialmemberscount);
			}
		}

		$availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

		if (empty($availableusers)) {
			return array();
		}


		if ($search) {
			$groupname = get_string('subscribedusersmatching', 'newsletter', $search);
		} else {
			$groupname = get_string('subscribedusers', 'newsletter');
		}

		return array($groupname => $availableusers);
	}

	protected function get_options() {
		$options = parent::get_options();
		$options['newsletterid'] = $this->newsletterid;
		$options['file']    = 'mod/newsletter/classes/newsletter_user_subscription.php';
		return $options;
	}
}