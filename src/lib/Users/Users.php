<?php
require_once 'config/config.php';

class Users
{
    const ALLOWED_TYPES = ['super', 'admin', 'user'];

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * Server-side validation of username/type. Returns an error message (string) or null if valid.
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

    /**
     * True if the logged-in admin is allowed to manage (edit/delete) this user record.
     * Super can always manage everyone.
     */
    private function canManage($target_user) {
        if ($_SESSION['type'] === 'super') {
            return true;
        }

        if ($_SESSION['type'] === 'admin') {
            return $target_user !== null
                && $target_user['type'] === 'user'
                && (int) $target_user['owner_admin_id'] === (int) $_SESSION['user_id'];
        }

        return false;
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
     * Add user.
     *
     * A 'super' account can create any type freely (owner_admin_id stays NULL: company-wide).
     * An 'admin' account can only create their own read-only 'user' accounts
     * (type is forced to 'user', owner_admin_id is forced to their own id).
     */
    public function addUser($input_data) {
        $db = getDbInstance();

        $requested_type = $input_data['type'] ?? '';
        $owner_admin_id = null;

        if ($_SESSION['type'] === 'admin') {
            $requested_type = 'user';
            $owner_admin_id = $_SESSION['user_id'];
        } elseif ($_SESSION['type'] !== 'super') {
            header('HTTP/1.1 403 Forbidden', true, 403);
            exit('403 Forbidden');
        }

        $validation_error = $this->validateUsernameAndType($input_data['username'] ?? '', $requested_type);
        if ($validation_error !== null) {
            $this->failure($validation_error, 'Location: user.php');
        }

        if (!isset($input_data['password']) || strlen($input_data['password']) < 10) {
            $this->failure('Password must be at least 10 characters long.', 'Location: user.php');
        }

        $data_to_db["username"] = $input_data["username"];
        $data_to_db['password'] = password_hash($input_data['password'], PASSWORD_DEFAULT);
        $data_to_db["type"] = $requested_type;
        $data_to_db['owner_admin_id'] = $owner_admin_id;
        $data_to_db['can_view_static'] = !empty($input_data['can_view_static']) ? 1 : 0;
        $data_to_db['can_view_dynamic'] = !empty($input_data['can_view_dynamic']) ? 1 : 0;

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
     * Edit user.
     *
     * An 'admin' may only edit their own 'user' accounts (checked via canManage()) and
     * cannot change the type away from 'user'. A 'super' account can edit anyone freely.
     */
    public function editUser($input_data) {
        $db = getDbInstance();

        $db->where('id', $input_data['id']);
        $target = $db->getOne('users');

        if (!$this->canManage($target)) {
            header('HTTP/1.1 403 Forbidden', true, 403);
            exit('403 Forbidden');
        }

        $query_string = http_build_query(array(
            'id' => $input_data["id"],
            'edit' => "true",
        ));

        $is_self_edit = (int) $input_data['id'] === (int) $_SESSION['user_id'];

        // A user editing their own account keeps their current type, even if a
        // different value was submitted - prevents accidentally (or deliberately)
        // locking yourself out by downgrading your own access level.
        $requested_type = $_SESSION['type'] === 'admin'
            ? 'user'
            : ($is_self_edit ? $target['type'] : ($input_data['type'] ?? ''));

        $validation_error = $this->validateUsernameAndType($input_data['username'] ?? '', $requested_type);
        if ($validation_error !== null) {
            $this->failure($validation_error, 'Location: user.php?'.$query_string);
        }

        if (isset($input_data['password']) && strlen($input_data['password']) > 0 && strlen($input_data['password']) < 10) {
            $this->failure('Password must be at least 10 characters long.', 'Location: user.php?'.$query_string);
        }

        $db = getDbInstance();
        $db->where('username', $input_data['username']);
        $db->where('id', $input_data["id"], '!=');
        $row = $db->getOne('users');

        if (!empty($row['username']))  {
            $this->failure('Username already exists', 'Location: user.php?'.$query_string);
        }

        $data_to_db["username"] = $input_data["username"];
        $data_to_db["type"] = $requested_type;
        $data_to_db['can_view_static'] = !empty($input_data['can_view_static']) ? 1 : 0;
        $data_to_db['can_view_dynamic'] = !empty($input_data['can_view_dynamic']) ? 1 : 0;

        // Only overwrite the password if a new value was submitted.
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
     * Delete user.
     *
     * An 'admin' may only delete their own 'user' accounts; 'super' can delete anyone.
     */
    public function deleteUser($id) {
        $db = getDbInstance();
        $db->where('id', $id);
        $target = $db->getOne('users');

        if (!$this->canManage($target)) {
            header('HTTP/1.1 403 Forbidden', true, 403);
            exit('403 Forbidden');
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
