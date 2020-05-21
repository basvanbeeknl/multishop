<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
if ($this->ADMIN_USER) {
    $return_data = array();
    $limit = 100;
    $filter = array();
    if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id']) && $this->get['preselected_id'] > 0 && $this->get['preselected_id'] != 'all') {
        $filter[] = 'manufacturers_id IN (' . $this->get['preselected_id'] . ')';
    }
    if (!empty($this->get['q'])) {
        $filter[] = '(manufacturers_name like \'%' . $this->get['q'] . '%\')';
        $limit = '';
    }
    $counter = 0;
    if (!count($filter)) {
        if (!isset($this->get['tx_multishop_pi1']['no_extra_label'])) {
            $return_data[0]['text'] = htmlentities($this->pi_getLL('admin_choose_manufacturer'));
            $return_data[0]['id'] = '0';
            $counter = 1;
        }
    }
    if (count($filter) || (isset($this->get['q']) && empty($this->get['q']))) {
		$return_data[$counter]['text'] = htmlentities($this->pi_getLL('all'));
		$return_data[$counter]['id'] = 'all';
		$counter++;
    	$query = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                'tx_multishop_manufacturers', // FROM ...
                implode(' and ', $filter), // WHERE...
                '', // GROUP BY...
                'manufacturers_name asc', // ORDER BY...
                $limit // LIMIT ...
        );
        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $return_data[$counter]['text'] = htmlentities($row['manufacturers_name']);
                $return_data[$counter]['id'] = $row['manufacturers_id'];
                $counter++;
            }
        }
    }
	if (isset($this->get['preselected_id'])) {
		if ($this->get['preselected_id'] == 'all') {
			$return_data = array();
			$return_data[0]['text'] = htmlentities($this->pi_getLL('all'));
			$return_data[0]['id'] = 'all';
		}
	}
    echo json_encode($return_data);
    exit;
}
exit();
?>