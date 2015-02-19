<?php

namespace App\Library;

use App\Models\Users;
use App\Models\Tokens;

class Auth extends \Phalcon\Mvc\User\Component
{

    private $_config = array();
    //private static $_instance;
    /**
     * Singleton pattern
     *
     * @return Auth instance
     */
    // Use DI->setShared without Singleton
    /*public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new Auth;
        }

        return self::$_instance;
    }*/

    public function __construct()
    {
        // Overwrite _config from config.ini
        if ($_config = $this->config->auth) {
            foreach ($_config as $key => $value) {
                $this->_config[$key] = $value;
            }
        }
    }

    /**
     * Private clone - disallow to clone the object
     */
    private function __clone()
    {

    }

    /**
     * Checks if a session is active.
     *
     * @param mixed $role role name
     *
     * @return boolean
     */
    public function logged_in($role = null)
    {
        // Get the user from the session
        $user = $this->get_user();
        if (!$user) {
            return false;
        }

        // If user exists in session
        if ($user) {
            // If we don't have a roll no further checking is needed
            if (!$role) {
                return true;
            }

            if ($this->_config['session_role'] && $this->session->has($this->_config['session_role'])) {
                // Check in session
                $role = $this->session->get($this->_config['session_role']) == $role;
            } else {
                // Check in db
                $role = $user->type == $role;
            }

            // Return true if user has role
            return $role ? true : false;
        }
    }

    /**
     * Gets the currently logged in user from the session.
     * Returns null if no user is currently logged in.
     *
     * @return mixed
     */
    public function get_user()
    {
        $user = unserialize($this->session->get($this->_config['session_key']));

        // Check for "remembered" login
        if (!$user) {
            $user = $this->auto_login();
        }

        return $user;
    }

    /**
     * Refresh user data stored in the session from the database.
     * Returns null if no user is currently logged in.
     *
     * @return mixed
     */
    public function refresh_user()
    {
        $user = unserialize($this->session->get($this->_config['session_key']));

        if (!$user) {
            return null;
        } else {
            // Get user's data from db
            $user = Users::findFirst($user->id);
            $role = $user->type;

            // Regenerate session_id
            session_regenerate_id();

            // Store user in session
            $this->session->set($this->_config['session_key'], serialize($user));
            // Store user's roles in session
            if ($this->_config['session_role']) {
                $this->session->set($this->_config['session_role'], $role);
            }

            return $user;
        }
    }

    /**
     * Complete the login for a user by incrementing the logins and saving login timestamp
     *
     * @param object $user user from the model
     *
     * @return void
     */
    private function complete_login($user)
    {
        // Update the number of logins
        $user->logins = $user->logins + 1;

        // Set the last login date
        $user->last_login = time();
        $this->last_login_ip = $this->request->getClientAddress();

        // Save the user
        $user->update();
    }

    /**
     * Logs a user in, based on the authautologin cookie.
     *
     * @return mixed
     */
    private function auto_login()
    {
        if ($this->cookies->has('authautologin')) {
            $cookieToken = $this->cookies->get('authautologin')->getValue('string');

            // Load the token
            $token = Tokens::findFirst(array('token=:token:', 'bind' => array(':token' => $cookieToken)));

            // If the token exists
            if ($token) {
                // Load the user and his roles
                $user = $token->getUser();

                // If tokens match, perform a login
                if ($token->user_agent === sha1($this->request->getUserAgent())) {
                    // Save the token to create a new unique token
                    $token->token = $this->create_token();
                    $token->save();

                    // Set the new token
                    $this->cookies->set('authautologin', $token->token, $token->expires);

                    // Finish the login
                    $this->complete_login($user);

                    // Regenerate session_id
                    session_regenerate_id();

                    // Store user in session
                    $this->session->set($this->_config['session_key'], serialize($user));
                    // Store user's roles in session
                    if ($this->_config['session_role']) {
                        $this->session->set($this->_config['session_role'], $user->type);
                    }

                    // Automatic login was successful
                    return $user;
                }

                // Token is invalid
                $token->delete();
            } else {
                $this->cookies->set('authautologin', "", time() - 3600);
                $this->cookies->delete('authautologin');
            }
        }

        return false;
    }

    /**
     * Attempt to log in a user by using an ORM object and plain-text password.
     *
     * @param string $email email to log in
     * @param string $password password to check against
     * @param boolean $remember enable autologin
     * @return boolean
     */
    public function login($user, $password, $remember = false)
    {
        if (!is_object($user)) {
            $email = $user;

            // email not specified
            if (!$email) {
                return null;
            }

            // Load the user
            $user = Users::findFirst(array('email=:email:', 'bind' => array(':email' => $email)));
        }

        if ($user) {

            // Create a hashed password
            if (is_string($password)) {
                $password = $this->hash($password);
            }

            // If user have login role and the passwords match, perform a login
            if ($user->password === $password) {
                if ($remember === true) {
                    // Create a new autologin token
                    $token = new Tokens();
                    $token->user_id = $user->id;
                    $token->user_agent = sha1($this->request->getUserAgent());
                    $token->token = $this->create_token();
                    $token->created = time();
                    $token->expires = time() + $this->_config['lifetime'];

                    if ($token->create() === true) {
                        // Set the autologin cookie
                        $this->cookies->set('authautologin', $token->token, time() + $this->_config['lifetime']);
                    }
                }

                // Finish the login
                $this->complete_login($user);
                // Regenerate session_id
               // session_regenerate_id();

                // Store user in session
                $this->session->set($this->_config['session_key'], serialize($user));
                // Store user's roles in session
                if ($this->_config['session_role']) {
                    $this->session->set($this->_config['session_role'], $user->type);
                }

                return true;
            } else {
                // Login failed
                return false;
            }
        }
        // No user found
        return null;
    }

    /**
     * Log out a user by removing the related session variables
     * Remove any autologin cookies.
     *
     * @param boolean $destroy completely destroy the session
     * @param boolean $logoutAll remove all tokens for user
     * @return boolean
     */
    public function logout($destroy = false, $logoutAll = false)
    {
        if ($this->cookies->has('authautologin')) {
            $cookieToken = $this->cookies->get('authautologin')->getValue('string');

            // Delete the autologin cookie to prevent re-login
            $this->cookies->set('authautologin', "", time() - 3600);
            $this->cookies->delete('authautologin');

            // Clear the autologin token from the database
            $token = Tokens::findFirst(array('token=:token:', 'bind' => array(':token' => $cookieToken)));

            if ($logoutAll) {
                // Delete all user tokens
                foreach (Tokens::find(array('user_id=:user_id:', 'bind' => array(':user_id' => $token->user_id))) as $_token) {
                    $_token->delete();
                }
            } else {
                if ($token) {
                    $token->delete();
                }
            }
        }

        // Destroy the session completely
        if ($destroy === true) {
            $this->session->destroy();
        } else {
            // Remove the user from the session
            $this->session->remove($this->_config['session_key']);
            // Remove user's roles from the session
            if ($this->_config['session_role']) {
                $this->session->remove($this->_config['session_role']);
            }

            // Regenerate session_id
            session_regenerate_id();
        }

        // Double check
        return !$this->logged_in();
    }

    /**
     * Perform a hmac hash, using the configured method.
     *
     * @param string $hash string to hash
     * @return string
     */
    public function hash($str)
    {
        if (!$this->_config['hash_key']) {
            throw new \Phalcon\Exception('A valid hash key must be set in your auth config.');
        }

        return hash_hmac($this->_config['hash_method'], $str, $this->_config['hash_key']);
    }

    /**
     * Create auto login token.
     *
     * @return  string
     */
    protected function create_token()
    {
        do {
            $token = sha1(uniqid(\Phalcon\Text::random(\Phalcon\Text::RANDOM_ALNUM, 32), true));
            //$token = sha1(uniqid(mt_rand(), true));
        } while (Tokens::findFirst(array('token=:token:', 'bind' => array(':token' => $token))));

        return $token;
    }

}