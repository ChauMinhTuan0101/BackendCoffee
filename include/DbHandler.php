<?php

class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }


    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `User -PhucVu -Bartender` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($type, $name, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        // Generating password hash
        $password_hash = PassHash::hash($password);

        // Generating API key
        $api_key = $this->generateApiKey();

        // insert query
        $stmt = $this->conn->prepare("INSERT INTO tbUser(UserType, UserName, Password, api_key) values(?, ?, ?, ?)");
        $stmt->bind_param("isss", $type, $name, $password_hash,$api_key);

        $result = $stmt->execute();

        $stmt->close();

        // Check for successful insertion
        if ($result) {
            // User successfully inserted
            return USER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return USER_CREATE_FAILED;
        }

        return $response;
    }

    public function getApiKeyByUser($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM tbUser WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    public function getApiKeyByUsername($user_name){
        $stmt = $this->conn->prepare("SELECT api_key from tbUser where UserName = ?");
        $stmt->bind_param("s",$user_name);
        if($stmt->excute()){
            $stmt->bind_result($api_key);
            $res["api_key"] = $api_key;
            $stmt->close();
            return $res;
        }else{
            return NULL;
        }
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($UserName, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT Password FROM tbUser WHERE UserName = ?");

        $stmt->bind_param("s", $UserName);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($UserName) {
        $stmt = $this->conn->prepare("SELECT UserID, UserName, UserType, api_key FROM tbUser WHERE UserName = ?");
        $stmt->bind_param("s", $UserName);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($id, $name, $type, $apikey);
            $stmt->fetch();
            $user = array();
            $user["id"] = $id;
            $user["name"] = $name;
            $user["type"] = $type;
            $user["api_key"] = $apikey;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }


    /**
     * get User ID by api_key
     * @param api_key
     * @return null
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT UserID FROM tbUser WHERE api_key =?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * check valid api_key
     * @param $api_key
     * @return bool
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT UserID from tbUser WHERE api_key =?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Creating new Bartender
     * @param int $userid User ID
     * @param int $level User level
     */
    public function createBartender($userid, $level) {
        require_once 'PassHash.php';
        $response = array();
        // insert query
        $stmt = $this->conn->prepare("INSERT INTO tbBartender(UserID, UserLevel) values(?, ?)");
        $stmt->bind_param("ii", $userid, $level);
        $result = $stmt->execute();
        $stmt->close();
        // Check for successful insertion
        if ($result) {
            // User successfully inserted
            return BARTENDER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return BARTENDER_CREATE_FAILED;
        }

        return $response;
    }

    /**
     * Creating new PhucVU
     * @param int $userid User ID
     * @param int $level User level
     */
    public function createPhucvu($userid, $level) {
        require_once 'PassHash.php';
        $response = array();
        // insert query
        $stmt = $this->conn->prepare("INSERT INTO tbPhucVu(UserID, UserLevel) values(?, ?)");
        $stmt->bind_param("ii", $userid, $level);
        $result = $stmt->execute();
        $stmt->close();
        // Check for successful insertion
        if ($result) {
            // User successfully inserted
            return BARTENDER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return BARTENDER_CREATE_FAILED;
        }

        return $response;
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($username) {
        $stmt = $this->conn->prepare("SELECT UserName from tbUser WHERE UserName = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Get all Bartender
     * @return list of bartender
     */
    public function getBartender() {
        $stmt = $this->conn->prepare("SELECT * from tbBartender ");
        $stmt->execute();
        $bartenders = $stmt->get_result();
        $stmt->close();
        return $bartenders;
    }

    /* ------------- `OrderList` table method ------------------ */

    /**
     * Creating new OrderList
     * @param int $orderid_id of the order
     * @param int $idphucvu id of the waiter serve
     * @param int $idbartender id of the bartender serve
     * @param int $tablenumber number of the table
     * @param string $noticeinfo other notice for the order
     */
    public function createOrder($idphucvu,$idbartender,$tablenumber,$noticeinfo) {
        $response = array();

        $stmt = $this->conn->prepare("INSERT INTO tbOrderList(IDBartender,IDPhucVu,TableNumber,NoticeInfo) VALUES(?,?,?,?)");
        $stmt->bind_param("iiis", $idbartender,$idphucvu,$tablenumber,$noticeinfo);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            //Succesful
            return 0;
        } else {
            //Failed
            return 1;
        }


    }

    /**
     * Creating detail  of the order
     * @param $orderid the orderid was created
     * @param $itemid id of the item
     * @param $itemprice price of the item
     * @param $quantity quantity of the item
     * @return int
     */
    public function createOrderDetail($orderid,$itemid,$itemprice,$quantity)
    {

        $response = array();
        $stmt = $this->conn->prepare("INSERT INTO tbOrder(OrderID,ItemID,ItemPrice,Quantity) VALUES(?,?,?,?)");
        $stmt->bind_param("iiii",$orderid, $itemid,$itemprice,$quantity);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            //Succesful
            return 0;
        } else {
            //Failed
            return 1;
        }


    }
    //UPDATE `CoffeeDatabase`.`tbOrderList` SET `Serving`='1' WHERE `OrderID`='153';
    public function updateServingStatus($serving,$orderid)
    {
        $response = array();
        $stmt = $this->conn->prepare("UPDATE tbOrderList SET Serving = ? WHERE OrderID = ?");
        $stmt->bind_param("ii",$serving, $orderid);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            //Succesful
            return 0;
        } else {
            //Failed
            return 1;
        }
    }

    public function getValueToGetPrice()
    {

    }


    public function addMenuItem($itemname,$itemtype,$itemprice,$itemdesc)
    {
        $response = array();
        $stmt = $this->conn->prepare("INSERT INTO tbMenuItem(ItemName,ItemType,ItemPrice,ItemDescription) VALUES(?,?,?,?)");
        $stmt->bind_param("siis",$itemname, $itemtype,$itemprice,$itemdesc);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            //Succesful
            return 0;
        } else {
            //Failed
            return 1;
        }
    }


    public function inCompleteOrder($serving)
    {
        $stmt = $this->conn->prepare("SELECT OrderID, TableNumber FROM tbOrderList WHERE Serving = ?");
        $stmt->bind_param("i", $serving);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        } else {
            return NULL;
        }
    }
    public function getAllOrderDetail()
    {
        $stmt = $this->conn->prepare("SELECT  * from tbOrder");
        $stmt->execute();
        $menuitem = $stmt->get_result();
        $stmt->close();
        return $menuitem;
    }
    public function getOrderDetailByOrderID($orderid)
    {
        $stmt = $this->conn->prepare("SELECT  * from tbOrder where OrderID = ?");
        $stmt -> bind_param("i",$orderid);
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            $stmt->close();
            return $result;
        } else {
            return NULL;
        }

    }
    public function getLatestOrderID()
    {
        $stmt = $this->conn->prepare("SELECT OrderID FROM tbOrderList WHERE OrderID = ( SELECT MAX(OrderID) FROM tbOrderList) ");
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        } else {
            return NULL;
        }
    }


    public function getListOrder()
    {
        $stmt = $this->conn->prepare("SELECT  * from tbOrderList");
        $stmt->execute();
        $menuitem = $stmt->get_result();
        $stmt->close();
        return $menuitem;
    }

    /**
     * Add new Menu Item
     * @param $itemname name of the item
     * @param $itemtype type of the item
     * @param $itemprice price of the item
     * @param $itemdesc more information of the item
     * @return int
     */
    public function createMenuItem($itemname,$itemtype,$itemprice,$itemdesc)
    {

        $stmt = $this->conn->prepare("INSERT INTO tbMenuItem(ItemName,ItemType,ItemPrice,ItemDescription) VALUES(?,?,?,?)");
        $stmt->bind_param("siii",$itemname,$itemtype,$itemprice,$itemdesc);
        $result = $stmt->excute();
        $stmt->close();
        if($result)
        {
            //successful
            return 0;
        } else{
            return 1;
        }

    }

    public function getMenuItem()
    {
        $stmt = $this->conn->prepare("SELECT  * from tbMenuItem");
        $stmt->execute();
        $menuitem = $stmt->get_result();
        $stmt->close();
        return $menuitem;
    }





    /* ------------- Unprocessing table method ------------------ */

    /**
     * Fetching user by email
     * @param String $email User email id
     */
//    public function getUserByEmail($UserName) {
//        $stmt = $this->conn->prepare("SELECT UserID, UserName, UserType FROM tbUser WHERE UserName = ?");
//        $stmt->bind_param("s", $UserName);
//        if ($stmt->execute()) {
//            // $user = $stmt->get_result()->fetch_assoc();
//            $stmt->bind_result($id, $name, $type);
//            $stmt->fetch();
//            $user = array();
//            $user["id"] = $id;
//            $user["name"] = $name;
//            $user["type"] = $type;
//            $stmt->close();
//            return $user;
//        } else {
//            return NULL;
//        }
//    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
//    public function getApiKeyById($user_id) {
//        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
//        $stmt->bind_param("i", $user_id);
//        if ($stmt->execute()) {
//            // $api_key = $stmt->get_result()->fetch_assoc();
//            // TODO
//            $stmt->bind_result($api_key);
//            $stmt->close();
//            return $api_key;
//        } else {
//            return NULL;
//        }
//    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
//    public function getUserId($api_key) {
//        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
//        $stmt->bind_param("s", $api_key);
//        if ($stmt->execute()) {
//            $stmt->bind_result($user_id);
//            $stmt->fetch();
//            // TODO
//            // $user_id = $stmt->get_result()->fetch_assoc();
//            $stmt->close();
//            return $user_id;
//        } else {
//            return NULL;
//        }
//    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
//    public function isValidApiKey($api_key) {
//        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
//        $stmt->bind_param("s", $api_key);
//        $stmt->execute();
//        $stmt->store_result();
//        $num_rows = $stmt->num_rows;
//        $stmt->close();
//        return $num_rows > 0;
//    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
//    private function generateApiKey() {
//        return md5(uniqid(rand(), true));
//    }

    /**
     * Function to create order detail
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
//    public function getTask($task_id, $user_id) {
//        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
//        $stmt->bind_param("ii", $task_id, $user_id);
//        if ($stmt->execute()) {
//            $res = array();
//            $stmt->bind_result($id, $task, $status, $created_at);
//            // TODO
//            // $task = $stmt->get_result()->fetch_assoc();
//            $stmt->fetch();
//            $res["id"] = $id;
//            $res["task"] = $task;
//            $res["status"] = $status;
//            $res["created_at"] = $created_at;
//            $stmt->close();
//            return $res;
//        } else {
//            return NULL;
//        }
//    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */



}

?>
