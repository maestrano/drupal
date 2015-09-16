<?php

/**
 * Configure App specific behavior for Maestrano SSO
 */
class MnoSsoUser extends Maestrano_Sso_User {
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct($saml_response) {
    parent::__construct($saml_response);
  }
  
  /**
  * Find or Create a user based on the SAML response parameter and Add the user to current session
  */
  public function findOrCreate() {
    // Find user by uid. Is it exists, it has already signed in using SSO
    $local_id = $this->getLocalIdByUid();
    $new_user = ($local_id == null);
    // Find user by email
    if($local_id == null) { $local_id = $this->getLocalIdByEmail(); }

    if ($local_id) {
      // User found, load it
      $this->local_id = $local_id;
      $this->syncLocalDetails($new_user);
    } else {
      // New user, create it
      $this->local_id = $this->createLocalUser();
      $this->setLocalUid();
    }

    // Add user to current session
    $this->setInSession();
  }
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession() {
    global $user;
    $user = db_query("SELECT * FROM users WHERE uid = :uid", array(':uid' => $this->local_id))->fetchObject();
    
    if ($user) {
        // Function uses $user as global variable
        $form_state['uid'] = $user->uid;
        
        user_login_finalize($form_state);
        
        return true;
    } else {
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser() {    
    // First build user
    $user_hash = $this->buildLocalUser();
    
    // Create user
    $user = user_save('',$user_hash);
    
    return $user->uid;
  }
  
  /**
   * Build a local user for creation
   *
   * @return the ID of the user created, null otherwise
   */
  protected function buildLocalUser() {
    $user = Array(
      'name'     => $this->formatUniqueUsername(),
      'mail'     => $this->getEmail(),
      'pass'     => $this->generatePassword(),
      'status'   => 1,
      'roles'    => $this->getRolesToAssign()
    );
    
    return $user;
  }
  
  /**
   * Return a unique username which is more user friendly
   * that just using the maestrano uid
   */
  public function formatUniqueUsername() {
    $s_name = preg_replace("/[^a-zA-Z0-9]+/", "", $this->getFirstName());
    $s_surname = preg_replace("/[^a-zA-Z0-9]+/", "", $this->getLastName());
    $formatted = $s_name . '_' . $s_surname . '_' . $this->uid;
    return $formatted;
  }
  
  /**
   * Return the rolse to give to the user based on context
   * If the user is the owner of the app or at least Admin
   * for each organization, then it is given the role of 'Admin'.
   * Return 'User' role otherwise
   *
   * @return the ID of the user created, null otherwise
   */
  public function getRolesToAssign() {
    // Drupal only look at the array **keys** to assign
    // to role. Content of the key is useless
    $default_user_role = Array(2 => 1);
    $default_admin_role = Array(3 => 3);

    switch($this->getGroupRole()) {
      case 'Member':
        return $default_user_role;
      case 'Power User':
        return $default_user_role;
      case 'Admin':
        return $default_admin_role;
      case 'Super Admin':
        return $default_admin_role;
      default:
        return $default_user_role;
    }
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid() {
    $user = db_query("SELECT uid FROM users WHERE mno_uid = :uid", array(':uid' => $this->uid))->fetchObject();
    
    if ($user && $user->uid) {
      return intval($user->uid);
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail() {
    $user = db_query("SELECT uid FROM users WHERE mail = :email", array(':email' => $this->getEmail()))->fetchObject();
    
    if ($user && $user->uid) {
      return intval($user->uid);
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       $upd = db_update('users')
         ->fields(array(
          'name' => $this->formatUniqueUsername(),
          'mail' => $this->getEmail()
        ))
        ->condition('uid', $this->local_id)
        ->execute();
       
       return $upd;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid() {
    if($this->local_id) {
      $upd = db_update('users')
        ->fields(array(
         'mno_uid' => $this->uid
       ))
       ->condition('uid', $this->local_id)
       ->execute();
      
      return $upd;
    }
    
    return false;
  }
  
  /**
  * Generate a random password.
  * Convenient to set dummy passwords on users
  *
  * @return string a random password
  */
  protected function generatePassword() {
    $length = 20;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }
}