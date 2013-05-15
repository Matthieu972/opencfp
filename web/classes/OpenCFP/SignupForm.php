<?php
namespace OpenCFP;

/**
 * Form object for our signup page, handles validation of form data
 */
class SignupForm
{
	protected $_data;
	protected $_passwordMessages = '';
	protected $_purifier;

    /**
     * Class constructor
     *
     * @param $data array of $_POST data
     */
    public function __construct($data)
    {
    	$this->_data = $data;
    	$config = \HTMLPurifier_Config::createDefault();
    	$this->_purifier = new \HTMLPurifier($config);
    }

    /**
     * Method verifies we have all required fields in our POST data
     *
     * @returns boolean
     */
    public function hasRequiredFields()
    {
        // If any of our fields are empty, reject stuff
	    $fieldList = array(
	        'email', 
	        'password', 
	        'password2', 
	        'first_name', 
	        'last_name',
	        'speaker_info'
	    );

	    foreach ($fieldList as $field) {
	        if (!isset($this->_data[$field])) {
	            $allFieldsFound = false;
	            break;
	        }
	    }

	    return $allFieldsFound;
	}

	/**
	 * Validate all methods by calling all our validation methods
	 *
	 * @return boolean
	 */
	public function validateAll()
	{
		/**
		 * First, use a circuit breaker to determine if the data the users
		 * have submitted survives being sanitized
		 */

		// Grab all our fields we are expecting
		// apply HTMLPurifier to it
		// do a comparison and fail the check if they don't match
		$sanitizedData = $this->sanitize();
		$originalData = array(
			'email' => $this->_data['email'],
			'password' => $this->_data['password'],
			'password2' => $this->_data['password2'],
			'firstName' => $this->_data['firstName'],
			'lastName' => $this->_data['lastName']
		);

		if (isset($this->_data['speaker_info'])) {
			$originalData['speaker_info'] = $this->_data['speaker_info'];
		}

		$differences = array_diff($originalData, $sanitizedData);

		if (count($differences) > 0) {
			return false;
		}

		return (
			$this->validateEmail() ||
			$this->validatePasswords() ||
			$this->validateFirstName() ||
			$this->validateLastName()
		);
	}

	/**
	 * Method that applies validation rules to email 
	 *
	 * @param string $email
	 */
	public function validateEmail()
	{
		if (!isset($this->_data['email'])) {
			return false;
		}

		$response = filter_var($this->_data['email'], FILTER_VALIDATE_EMAIL);

		return ($response !== false);
	}

	/**
	 * Method that applies validation rules to user-submitted passwords
	 *
	 * @return true|string
	 */
	public function validatePasswords()
	{
		$passwd = filter_var($this->_data['password'], FILTER_SANITIZE_STRING);
		$passwd2 = filter_var($this->_data['password2'], FILTER_SANITIZE_STRING);

		if ($passwd == '' || $passwd2 == '') {
			return "Missing passwords";
		}

		if ($passwd !== $passwd2) {
	        return "The submitted passwords do not match";
	    }

	    if (strlen($passwd) < 5 && strlen($passwd2) < 5) {
            return "Your password must be at least 5 characters";
	    }

	    return true; 
	}

	/**
	 * Method that applies vaidation rules to user-submitted first names
	 * 
	 * @return boolean
	 */
	public function validateFirstName()
	{
		$firstName = filter_var(
			$this->_data['firstName'], 
			FILTER_SANITIZE_STRING, 
			array('flags' => FILTER_FLAG_STRIP_HIGH)
		);

		if ($firstName == '') {
			return false;
		}

		if (strlen($firstName) > 255) {
			return false;
		}

		if ($firstName !== $this->_data['firstName']) {
			return false;
		}

		return true;
	}


	/**
	 * Method that applies vaidation rules to user-submitted first names
	 * 
	 * @return boolean
	 */
	public function validateLastName()
	{
		$lastName = filter_var(
			$this->_data['lastName'], 
			FILTER_SANITIZE_STRING, 
			array('flags' => FILTER_FLAG_STRIP_HIGH)
		);

		$lastName = strip_tags($lastName);

		if ($lastName == '') {
			return false;
		}

		if (strlen($lastName) > 255) {
			return false;
		}

		if ($lastName !== $this->_data['lastName']) {
			return false;
		}

		return true;
	}

	/**
	 * Santize all our fields that were submitted
	 *
	 * @return array
	 */
	public function sanitize()
	{
		$sanitizedData = array();

		foreach ($this->_data as $key => $value) {
			$sanitizedData[$key] = $this->_purifier->purify($value); 
		}

		return $sanitizedData;
	}
}
