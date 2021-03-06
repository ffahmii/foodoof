<?php

class User extends DataMapper {

    var $table = "users"; 
    var $has_many = array('recipe', 'comment', 'conversation', 'conversation_list');
    //var $has_one = array('country');

    var $validation = array(
        'email' => array(
            'label' => 'Email Address',
            'rules' => array('required', 'trim', 'notmember', 'valid_email'),
        ),
        'confirm_password' => array(
            'label' => 'Confirm Password',
            'rules' => array('matches' => 'password'),
        ),
        'password' => array(
            'label' => 'Password',
            'rules' => array('required', 'min_length' => 6, 'encrypt'),
        ),
        'name' => array(
            'label' => 'Your Name',
            'rules' => array('trim', 'max_length' => 255)
        ),
    );

    function __construct($user_id = NULL)
    {
        parent::__construct($user_id);
    }

    function login()
    {
        // Create a temporary user object
        $u = new User();

        // Get this users stored record via their username
        $ci =& get_instance();
        $ci->load->library('encrypt');
        $ci->encrypt->set_cipher(MCRYPT_RIJNDAEL_256);
        $ci->encrypt->set_mode(MCRYPT_MODE_CBC);
        
        if($u->where('email', $this->email)->count() != 1){
            // Login failed, so set a custom error message
            $this->error_message('login', 'Email address or password invalid');
            return FALSE;
        }
        else{
            $u->where('email', $this->email)->get();
            $decryptpass = $ci->encrypt->decode($u->user_password);
            if($this->user_password != $decryptpass){
                // Login failed, so set a custom error message
                $this->error_message('login', 'Email address or password invalid');
                return FALSE;
            }
            else{
                // Login succeeded and set session by user id.
                $ci->load->library('session');
                $ci->session->set_userdata('user_id', $u->id);
                $this->access();
                return TRUE;
            }
        }
    }

    function remember(){
        if(!empty($this->email)){
            $u = new User();
            if($u->get_by_email($this->email)->count()!=0){
                $ci =& get_instance();
                $ci->load->library('encrypt');
                $ci->encrypt->set_cipher(MCRYPT_RIJNDAEL_256);
                $ci->encrypt->set_mode(MCRYPT_MODE_CBC);
                return $ci->encrypt->decode($u->get_by_email($this->email)->password); 
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    function isLogin(){
        $ci =& get_instance();
        $ci->load->library('session');

        if($ci->session->userdata('user_id')==""){
            // User need login first
            return FALSE;
        }
        else{
            // User has login
            return TRUE;
        }
    }

    function access(){
        $ci =& get_instance();
        $ci->load->library('session');
        if(!$ci->session->userdata('user_id')==""){
            $u = new User();
            date_default_timezone_set('Asia/Jakarta');
            $now = Date("Y-m-d H:i:s");
            $u->where('id =', $ci->session->userdata('user_id'))->update("last_access", $now);
        }
    }

    // Validation prepping function to encrypt passwords
    // If you look at the $validation array, you will see the password field will use this function
    function _encrypt($field)
    {
        // Don't encrypt an empty string
        if (!empty($this->{$field}))
        {
            // Encrypty password
            $ci =& get_instance();
            $ci->load->library('encrypt');
            $ci->encrypt->set_cipher(MCRYPT_RIJNDAEL_256);
            $ci->encrypt->set_mode(MCRYPT_MODE_CBC);
            $passwordEncrypt = $ci->encrypt->encode($this->{$field});
            $this->{$field} = $passwordEncrypt;
        }
    }
    function _notmember($field){
        if (!empty($this->{$field}))
        {
            $u = new User();
            // Get email have used.
            if($u->where('email', $this->{$field})->count() === 0){
                return true;
            }
            else{
                $this->error_message('member', 'Email address is not available');
                return false;
            }
        }
        else{
            return false;
        }
    }
}

/* End of file user.php */
/* Location: ./application/models/user.php */