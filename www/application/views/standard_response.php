<?php
$response['status'] = $status;
$response['message'] = $message;
$response['result'] = $result;

echo stripslashes(json_encode($response));