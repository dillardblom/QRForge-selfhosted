<?php
require_once 'config/config.php';

class Users
{
    const ALLOWED_TYPES = ['super', 'admin'];

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * Server-side validatie van username/type. Geeft een foutmelding terug (string) of null als geldig.
     */
    private function validateUsernameAndType($username, $type) {
        if (!is_string($username) || strlen($username) < 3 || strlen($username) > 50) {
            return 'Username must be between 3 and 50 characters.';
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            return 'Username may only contain letters, numbers, dots, underscores and hyphens.';
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            return 'Invalid user type.';
        }

        return null;
    }

    /**
     *
     */
    public function __destruct()
    {
    }
    
    /**
     * Set friendly columns\' names to order tables\' entries
     */
    public function setOrderingValues()
    {
        $ordering = [
            'id' => 'ID',
            'username' => 'Username',
            'type' => 'Type'
        ];

        return $ordering;
    }

    public function getAllUsers() {
        $db = getDbInstance();
        return $db->get(DATABASE_PREFIX.'users');
    }

    public function getUser($id) {
        $db = getDbInstance();

        $db->where('id', $id);
        $result = $db->getOne('users');

        if($result !== NULL)
            return $result;
        else
            $this->failure("User not found");
    }
    
    /**
     * Add user
     */
    public function addUser($input_data) {
        $db = getDbInstance();

        $validation_error = $this->validateUsernameAndType($input_data['username'] ?? '', $input_data['type'] ?? '');
        if ($validation_error !== null) {
            $this->failure($validation_error, 'Location: user.php');
        }

        if (!isset($input_data['password']) || strlen($input_data['password']) < 10) {
            $this->failure('Password must be at least 10 characters long.', 'Location: user.php');
        }

        $data_to_db["username"] = $input_data["username"];
        $data_to_db['password'] = password_hash($input_data['password'], PASSWORD_DEFAULT);
        $data_to_db["type"] = $input_data["type"];

        $db->where('username', $data_to_db['username']);
        $db->get('users');

        if ($db->count >= 1)
            $this->failure('Username already exists');

	    $last_id = $db->insert('users', $data_to_db);

	    if ($last_id) {
		    audit_log('user_created', 'user', $last_id);
		    $this->success('User added successfully');
	    }
    }
    
    /**
     * Edit user
     * 
     */
    public function editUser($input_data) {
        $db = getDbInstance();

        $query_string = http_build_query(array(
            'id' => $input_data["id"],
            'edit' => "true",
        ));

        $validation_error = $this->validateUsernameAndType($input_data['username'] ?? '', $input_data['type'] ?? '');
        if ($validation_error !== null) {
            $this->failure($validation_error, 'Location: user.php?'.$query_string);
        }

        if (isset($input_data['password']) && strlen($input_data['password']) > 0 && strlen($input_data['password']) < 10) {
            $this->failure('Password must be at least 10 characters long.', 'Location: user.php?'.$query_string);
        }

        $db->where('username', $input_data['username']);
        $db->where('id', $input_data["id"], '!=');
        $row = $db->getOne('users');

        if (!empty($row['username']))  {
            $this->failure('Username already exists', 'Location: user.php?'.$query_string);
        }

        $data_to_db["username"] = $input_data["username"];
        $data_to_db["type"] = $input_data["type"];

        // Alleen wachtwoord overschrijven als er een nieuwe waarde is opgegeven.
        if (!empty($input_data['password'])) {
            $data_to_db['password'] = password_hash($input_data['password'], PASSWORD_DEFAULT);
        }

	    $db->where('id', $input_data["id"]);
	    $stat = $db->update('users', $data_to_db);

        if ($stat) {
            audit_log('user_updated', 'user', $input_data['id']);
            $this->success('User updated successfully!');
        } else
            $this->failure('Failed to update User: ' . $db->getLastError());
    }
    
    /**
     * Delete user
     * 
     */
    public function deleteUser($id) {
        if($_SESSION['type']!='super'){
            header('HTTP/1.1 401 Unauthorized', true, 401);
            exit("401 Unauthorized");
        }
        
        $db = getDbInstance();
        $db->where('id', $id);
        $stat = $db->delete('users');

        if ($stat) {
            audit_log('user_deleted', 'user', $id);
            $this->info('User deleted successfully!');
        } else
            $this->failure('Unable to delete user');
    }
    
    /**
     * Flash message Failure process
     */
    public function failure($message, $location = 'Location: users.php') {
        $_SESSION['failure'] = $message;
        header($location);
    	exit();
    }
    
    /**
     * Flash message Success process
     */
    public function success($message) {
        $_SESSION['success'] = $message;
        header('Location: users.php');
    	exit();
    }
    
    /**
     * Flash message Info process
     */
    public function info($message) {
        $_SESSION['info'] = $message;
        header('Location: users.php');
    	exit();
    }
}
?>
