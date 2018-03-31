<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('type', 'name', 'password'));
    $response = array();
    // reading post params
    $name = $app->request->post('type');
    $email = $app->request->post('name');
    $password = $app->request->post('password');
    // validating email address
    //validateEmail($email);
    $db = new DbHandler();
    $res = $db->createUser($name, $email, $password);
    if ($res == USER_CREATED_SUCCESSFULLY) {
        $response["error"] = false;
        $response["message"] = "You are successfully registered";
    } else if ($res == USER_CREATE_FAILED) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while registereing";
    } else if ($res == USER_ALREADY_EXISTED) {
        $response["error"] = true;
        $response["message"] = "Sorry, this email already existed";
    }
    // echo json response
    echoRespnse(201, $response);
});

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('UserName', 'password'));

    // reading post params
    $UserName = $app->request()->post('UserName');
    $password = $app->request()->post('password');
    $response = array();

    $db = new DbHandler();
    // check for correct email and password
    if ($db->checkLogin($UserName, $password)) {
        // get the user by email
        $user = $db->getUserByEmail($UserName);

        if ($user != NULL) {
            $response["error"] = false;
            $response['name'] = $user['name'];
            $response['id'] = $user['id'];
            $response['type'] = $user['type'];
            $response['api_key'] = $user['api_key'];
        } else {
            // unknown error occurred
            $response['error'] = true;
            $response['message'] = "An error occurred. Please try again";
        }
    } else {
        // user credentials are wrong
        $response['error'] = true;
        $response['message'] = 'Login failed. Incorrect credentials';
    }

    echoRespnse(200, $response);
});


/**
 * Add Bartender
 * url - /addbartender
 * method - POST
 * params - userid, level
 */
$app->post('/addbartender', function () use($app){
    verifyRequiredParams(array('userid','level'));
    $response = array();

    $userid = $app->request->post('userid');
    $level = $app->request->post('level');
    $db = new DbHandler();

    $res = $db->createBartender($userid,$level);

    if($res == BARTENDER_CREATED_SUCCESSFULLY)
    {
        $response["error"] = false;
        $response["message"] = "Add Bartender Successfully";

    } else if ($res = BARTENDER_CREATE_FAILED)
    {
        $response["error"] = true;
        $response["message"] = "Oops! An error occured while registereing";

    }
    echoRespnse(201,$response);
});

/**
 * Add Phuc Vu
 * url - /addphucvu
 * method - POST
 * params - userid, level
 */
$app->post('/addphucvu', function () use($app){
    verifyRequiredParams(array('userid','level'));
    $response = array();

    $userid = $app->request->post('userid');
    $level = $app->request->post('level');
    $db = new DbHandler();

    $res = $db->createPhucvu($userid,$level);

    if($res == BARTENDER_CREATED_SUCCESSFULLY)
    {
        $response["error"] = false;
        $response["message"] = "Add Bartender Successfully";

    } else if ($res = BARTENDER_CREATE_FAILED)
    {
        $response["error"] = true;
        $response["message"] = "Oops! An error occured while registereing";

    }
    echoRespnse(201,$response);
});




/**
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Create a new Order
 * method POST
 * url /createorder
 */

$app->post('/createorder','authenticate',function () use ($app){
    verifyRequiredParams(array('idphucvu','idbartender','tablenumber','noticeinfo'));
    $response = array();

    $idphucvu = $app->request->post('idphucvu');
    $idbartender = $app->request->post('idbartender');
    $tablenumber = $app->request->post('tablenumber');
    $noticeinfo = $app->request->post('noticeinfo');

    $db = new DbHandler();
    $res = $db->createOrder($idphucvu,$idbartender,$tablenumber,$noticeinfo);
    if ($res == 0) {
        $response["error"] = false;
        $response["message"] = "You are successfully Create Order";
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while ordering";
    }
    // echo json responses
    echoRespnse(201, $response);

});

/**
 * Add a new order detail
 * method POST
 * url /addorderdetail
 *
 */
$app->post('/addorderdetail','authenticate',function () use ($app){
    verifyRequiredParams(array('orderid','itemid','itemprice','quantity'));

    $response = array();

    $orderid = $app->request->post('orderid');
    $itemid = $app->request->post('itemid');
    $itemprice = $app->request->post('itemprice');
    $quantity = $app->request->post('quantity');

    $db = new DbHandler();
    $res = $db->createOrderDetail($orderid,$itemid,$itemprice,$quantity);
    if ($res == 0) {
        $response["error"] = false;
        $response["message"] = "You are successfully Create Order";
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while ordering";
    }
    // echo json responses
    echoRespnse(201, $response);
});

/**
 * Add new Menu Item
 *
 */
$app->post('/addmenuitem','authenticate',function () use($app){
   verifyRequiredParams(array('itemname','itemtype','itemprice','itemdesc'));
   $response = array();
   $itemname = $app->request->post('itemname');
   $itemtype = $app->request->post('itemtype');
   $itemprice = $app->request->post('itemprice');
   $itemdesc = $app->request->post('itemdesc');
   $db = new DbHandler();
   $res = $db->addMenuItem($itemname,$itemtype,$itemprice,$itemdesc);
   if($res==0){
       $response["error"] = false;
       $response["message"] = "You are sucessfully create menu Item";
   } else if ($res == 1) {
       $response["error"] = true;
       $response["message"] = "Oops! An error occurred while ordering";
   }
    // echo json responses
    echoRespnse(201, $response);



});

/**
 * Update Serving Status for Order
 * 1 = serving
 * 2 = complete
 *
 */
$app->post('/updateserving','authenticate',function () use ($app){
    verifyRequiredParams(array('serving','orderid'));
    $response = array();
    $serving = $app->request->post('serving');
    $orderid = $app->request->post('orderid');

    $db = new DbHandler();
    $res = $db->updateServingStatus($serving,$orderid);
    if ($res == 0) {
        $response["error"] = false;
        $response["message"] = "You are successfully Create Order";
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while ordering";
    }
    // echo json responses
    echoRespnse(201, $response);
});

/**
 *
 */
$app->post('/inserttable','authenticate',function ()use ($app){
    verifyRequiredParams(array('tableid'));
    $response = array();
    $id = $app->request->post('tableid');
    $db = new DbHandler();
    $res = $db->insertTable($id);
    if($res == 0)
    {
        $response["error"] = false;
        $response["message"] = "You are successfully Create Table";
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while creating table";
    }
    // echo json responses
    echoRespnse(201, $response);
});


$app->get('/gettable',function (){
    $db = new DbHandler();

    $result = $db->getTable();
    $response = array();
    while($task = $result->fetch_assoc()){
        $tmp = array();
        $tmp["tableID"] = $task["tableID"];
        $tmp["tableStatus"] = $task["tableStatus"];
        array_push($response,$tmp);

    }
    echoRespnse(201,$response);
});

$app->post('/changestatus','authenticate',function () use ($app){
   verifyRequiredParams(array('status','id'));
   $response = array();
   $status = $app->request->post('status');
   $id= $app->request->post('id');
   $db = new DbHandler();
   $res = $db->changeTableStatus($status,$id);

    if($res == 0)
    {
        $response["error"] = false;
        $response["message"] = "You are successfully Change Status Table";
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An error occurred while chaging Status table";
    }
    // echo json responses
    echoRespnse(201, $response);

});

/**
 * Add Menu Item
 * @param name,type,price,desc
 */
$app->post('/addmenuitem','authenticate',function () use ($app) {
    verifyRequiredParams(array('itemname', 'itemptype', 'itemprice', 'itemdesc'));
    $response = array();
    $itemname = $app->request->post('itemname');
    $itemtype = $app->request->post('itemtype');
    $itemprice = $app->request->post('itemprice');
    $itemdesc = $app->request->post('itemdesc');

    $db = new DbHandler();
    $res = $db->createMenuItem($itemname, $itemtype, $itemprice, $itemdesc);
    if ($res == 0) {
        $response["error"] = false;
        $response["message"] = "You have added successful Menu Item";
    } else if ($res == 1) {
        $response["error"] = true;
        $response["message"] = "Oops! An Error occurred while adding";
    }

});


/**
 * Get All Bartenders
 * #using for testing
 * @param none
 *
 */
$app->get('/bartenders', function (){
    $response = array();
    $db = new DbHandler();

    $result = $db->getBartender();
    //$response["error"] = false;
    $response = array();
    while($task = $result->fetch_assoc()){
        $tmp = array();
        $tmp["UserID"] = $task["UserID"];
        $tmp["DateJoin"] = $task["DateJoin"];
        $tmp["UserLevel"] = $task["UserLevel"];
        $tmp["BartenderName"] = $task["BartenderName"];
        array_push($response,$tmp);

    }
    echoRespnse(201,$response);
});
/**
 * get all order detail
 */
$app->get('/getallorderdetail','authenticate',function () use($app){
    $response = array();
    $db = new DbHandler();
    $result = $db->getAllOrderDetail();
    $response = array();
    while($menuitem = $result->fetch_assoc()){
        $tmp = array();
        $tmp["OrderID"]  = $menuitem["OrderID"];
        $tmp["ItemID"] = $menuitem["ItemID"];
        $tmp["ItemPrice"]= $menuitem["ItemPrice"];
        $tmp["Quantity"]= $menuitem["Quantity"];

        array_push($response,$tmp);
    }
    echoRespnse(201,$response);
});
/**
 * get order detail by orderID
 */
$app->post('/getorderdetailbyid','authenticate',function () use($app){
    verifyRequiredParams(array('orderid'));
    $response = array();
    $orderid = $app->request()->post("orderid");
    $db = new DbHandler();
    $result = $db->getOrderDetailByOrderID($orderid);
    while($menuitem = $result->fetch_assoc()){
        $tmp = array();
        $tmp["OrderID"]  = $menuitem["OrderID"];
        $tmp["ItemID"] = $menuitem["ItemID"];
        $tmp["ItemPrice"]= $menuitem["ItemPrice"];
        $tmp["Quantity"]= $menuitem["Quantity"];

        array_push($response,$tmp);
    }
    echoRespnse(201,$response);
});
/**
 * Get incomplete order
 *
 */
$app->post('/getincompleteorder','authenticate',function () use($app){
    verifyRequiredParams(array('serving'));
    $response = array();
    $serving = $app->request()->post("serving");
    $db = new DbHandler();
    $result = $db->inCompleteOrder($serving);
    while($incompleteorder = $result->fetch_assoc()){
        $tmp = array();
        $tmp["OrderID"]  = $incompleteorder["OrderID"];
        $tmp["TableNumber"] = $incompleteorder["TableNumber"];

        array_push($response,$tmp);
    }
    echoRespnse(201,$response);
});

/**
 * get menu items
 */
$app->get('/getmenuitems','authenticate',function () use($app){
   $response = array();
   $db = new DbHandler();
   $result = $db->getMenuItem();
   $response = array();
   while($menuitem = $result->fetch_assoc()){
       $tmp = array();
       $tmp["ItemID"]  = $menuitem["ItemID"];
       $tmp["ItemName"] = $menuitem["ItemName"];
       $tmp["ItemType"]= $menuitem["ItemType"];
       $tmp["ItemPrice"]= $menuitem["ItemPrice"];
       $tmp["ItemDescription"]= $menuitem["ItemDescription"];

       array_push($response,$tmp);
   }
   echoRespnse(201,$response);
});


/**
 * get list order
 */
$app->get('/getlistorders','authenticate',function () use($app){
    $response = array();
    $db = new DbHandler();
    $result = $db->getListOrder();
    $response = array();
    while($listorder = $result->fetch_assoc()){
        $tmp = array();
        $tmp["OrderID"]  = $listorder["OrderID"];
        $tmp["DateCreate"] = $listorder["DateCreate"];
        $tmp["IDBartender"]= $listorder["IDBartender"];
        $tmp["IDPhucVu"]= $listorder["IDPhucVu"];
        $tmp["TableNumber"]= $listorder["TableNumber"];
        $tmp["NoticeInfo"]= $listorder["NoticeInfo"];

        array_push($response,$tmp);
    }
    echoRespnse(201,$response);
});


$app->post('/getapifromuser','authenticate', function ($app){
    //global $user_name;
    verifyRequiredParams('username');
    $response = array();
    $username = $app->request()->post('username');

    $db= new DbHandler($username);

    $result = $db->getApiKeyByUsername($username);
    if($result != NULL){
        $response['api_key'] = $result['api_key'];
    } else {
        // unknown error occurred
        $response['error'] = true;
        $response['message'] = "An error occurred. Please try again";
    }


    echoRespnse(200, $response);
});

$app->get('/getlatestorderid','authenticate',function () use($app){
    $response = array();
    $db = new DbHandler();
    $result = $db->getLatestOrderID();
    $response = array();
    while($menuitem = $result->fetch_assoc()){

        $response = $menuitem;

    }
    echoRespnse(201,$response);
});

/**
 * ------------------------ UNPROCESSING METHODS ------------------------
 */

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks
 */
$app->get('/tasks', 'authenticate', function() {
    global $user_id;
    $response = array();
    $db = new DbHandler();

    // fetching all user tasks
    $result = $db->getAllUserTasks($user_id);

    $response["error"] = false;
    $response["tasks"] = array();

    // looping through result and preparing tasks array
    while ($task = $result->fetch_assoc()) {
        $tmp = array();
        $tmp["id"] = $task["id"];
        $tmp["task"] = $task["task"];
        $tmp["status"] = $task["status"];
        $tmp["createdAt"] = $task["created_at"];
        array_push($response["tasks"], $tmp);
    }

    echoRespnse(200, $response);
});

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
    global $user_id;
    $response = array();
    $db = new DbHandler();

    // fetch task
    $result = $db->getTask($task_id, $user_id);

    if ($result != NULL) {
        $response["error"] = false;
        $response["id"] = $result["id"];
        $response["task"] = $result["task"];
        $response["status"] = $result["status"];
        $response["createdAt"] = $result["created_at"];
        echoRespnse(200, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "The requested resource doesn't exists";
        echoRespnse(404, $response);
    }
});

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
    // check for required params
    verifyRequiredParams(array('task'));

    $response = array();
    $task = $app->request->post('task');

    global $user_id;
    $db = new DbHandler();

    // creating new task
    $task_id = $db->createTask($user_id, $task);

    if ($task_id != NULL) {
        $response["error"] = false;
        $response["message"] = "Task created successfully";
        $response["task_id"] = $task_id;
        echoRespnse(201, $response);
    } else {
        $response["error"] = true;
        $response["message"] = "Failed to create task. Please try again";
        echoRespnse(200, $response);
    }
});

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
    // check for required params
    verifyRequiredParams(array('task', 'status'));

    global $user_id;
    $task = $app->request->put('task');
    $status = $app->request->put('status');

    $db = new DbHandler();
    $response = array();

    // updating task
    $result = $db->updateTask($user_id, $task_id, $task, $status);
    if ($result) {
        // task updated successfully
        $response["error"] = false;
        $response["message"] = "Task updated successfully";
    } else {
        // task failed to update
        $response["error"] = true;
        $response["message"] = "Task failed to update. Please try again!";
    }
    echoRespnse(200, $response);
});

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
    global $user_id;

    $db = new DbHandler();
    $response = array();
    $result = $db->deleteTask($user_id, $task_id);
    if ($result) {
        // task deleted successfully
        $response["error"] = false;
        $response["message"] = "Task deleted succesfully";
    } else {
        // task failed to delete
        $response["error"] = true;
        $response["message"] = "Task failed to delete. Please try again!";
    }
    echoRespnse(200, $response);
});

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>