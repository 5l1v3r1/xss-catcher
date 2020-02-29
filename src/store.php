<?php
include_once './php/database.class.php';
header('Content-Type: application/json; charset=UTF-8');
$parameters = array(
    'data' => null,
    'site' => null,
    'info' => null
);
$proceed = true;
if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
    if (isset($_POST['site']) && isset($_POST['data'])) {
        $parameters['site'] = trim($_POST['site']);
        $parameters['data'] = $_POST['data'];
        // optional
        if (isset($_POST['info'])) {
            $parameters['info'] = trim($_POST['info']);
        }
    } else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = @json_decode($raw);
            if ($data) {
                if (isset($data->site) && isset($data->data)) {
                    $parameters['site'] = trim(urldecode($data->site));
                    $parameters['data'] = urldecode($data->data);
                    // optional
                    if (isset($data->info)) {
                        $parameters['info'] = trim(urldecode($data->info));
                    }
                }
            }
        }
    }
} else if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'get') {
    if (isset($_GET['site']) && isset($_GET['data'])) {
        $parameters['site'] = trim($_GET['site']);
        $parameters['data'] = $_GET['data'];
        // optional
        if (isset($_GET['info'])) {
            $parameters['info'] = trim($_GET['info']);
        }
    }
} else {
    $proceed = false;
}
if ($proceed) {
    $response = array(
        'status' => 'ok',
        'message' => array()
    );
    if (!is_null($parameters['site']) && !is_null($parameters['data'])) {
        mb_internal_encoding('UTF-8');
        $error = false;
        if (mb_strlen($parameters['site']) < 1) {
            $response['status'] = 'error';
            $response['message']['site'] = 'Site is required';
            $error = true;
        } else if (mb_strlen($parameters['site']) > 300) {
            $response['status'] = 'error';
            $response['message']['site'] = 'Site is exceeding 300 characters';
            $error = true;
        }
        if (mb_strlen($parameters['data']) < 1) {
            $response['status'] = 'error';
            $response['message']['site'] = 'Data is required';
            $error = true;
        } else if (mb_strlen($parameters['data']) > 1000) {
            $response['status'] = 'error';
            $response['message']['site'] = 'Data is exceeding 1000 characters';
            $error = true;
        }
		if (mb_strlen($parameters['info']) > 100) {
			$parameters['version'] = substr($parameters['version'], 0, 100);
		}
        if (!$error) {
            $db = new Database();
            if ($db->isConnected()) {
                $db->query('INSERT INTO `data` (`site`, `method`, `data`, `ip`, `date`, `info`) VALUES (:site, :method, :data, :ip, :date, :info)');
                $db->bind(':site', $parameters['site']);
                $db->bind(':method', $_SERVER['REQUEST_METHOD']);
                $db->bind(':data', $parameters['data']);
                $db->bind(':ip', $_SERVER['REMOTE_ADDR']);
                $db->bind(':date', date('Y-m-d H:i:s', time()));
                $db->bind(':info', $parameters['info']);
                if (!$db->execute()) {
                    $response['status'] = 'error';
                    $response['message']['global'] = 'Database error';
                }
            } else {
                $response['status'] = 'error';
                $response['message']['global'] = 'Database error';
            }
            $db->disconnect();
        }
    } else {
        $response['status'] = 'error';
        $response['message']['global'] = 'Required data is missing';
    }
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>
