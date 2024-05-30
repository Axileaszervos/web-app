<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

include '../connDB.php';
$username = $_SESSION['username'];

// Function to convert query result to XML
function queryToXML($tasklists, $tasks) {
    $xml = new SimpleXMLElement("<Tasklists/>");

    foreach ($tasklists as $tasklist) {
        $tasklistElement = $xml->addChild("Tasklist");
        $tasklistElement->addChild("ID", htmlspecialchars($tasklist['id']));
        $tasklistElement->addChild("Title", htmlspecialchars($tasklist['title']));
        $tasklistElement->addChild("UserName", htmlspecialchars($tasklist['username']));

        if (isset($tasks[$tasklist['id']])) {
            foreach ($tasks[$tasklist['id']] as $task) {
                $taskElement = $tasklistElement->addChild("Task");
                $taskElement->addChild("ID", htmlspecialchars($task['id']));
                $taskElement->addChild("Title", htmlspecialchars($task['title']));
                $taskElement->addChild("DateTime", htmlspecialchars($task['date_time']));
                $taskElement->addChild("Status", htmlspecialchars($task['status']));
                $taskElement->addChild("AssignedTo", htmlspecialchars($task['assigned_to']));
            }
        }
    }

    return $xml->asXML();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['export'] === 'tasklists_tasks') {
    // Fetch tasklists
    $sql_1 = "SELECT * FROM tasklists WHERE user_name= '$username' ORDER BY id DESC";
    $result_1 = $con->query($sql_1);

    $tasklists = [];
    $tasklist_ids = [];
    if ($result_1->num_rows > 0) {
        while ($row = $result_1->fetch_assoc()) {
            $tasklists[] = $row;
            $tasklist_ids[] = $row['id'];
        }
    }

    // Fetch tasks for the tasklists
    $tasks = [];
    if (!empty($tasklist_ids)) {
        $tasklist_ids_str = implode(',', $tasklist_ids);
        $sql_2 = "SELECT * FROM tasks WHERE tasklist_id IN ($tasklist_ids_str) ORDER BY date_time DESC";
        $result_2 = $con->query($sql_2);

        if ($result_2->num_rows > 0) {
            while ($row_2 = $result_2->fetch_assoc()) {
                $tasks[$row_2['tasklist_id']][] = $row_2;
            }
        }
    }

    $xmlContent = queryToXML($tasklists, $tasks);
    $fileName = "Tasklists_with_Tasks.xml";

    // Clear the output buffer to avoid any pre-output
    ob_clean();

    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo $xmlContent;

    // Ensure no further output is sent
    exit();
}

$con->close();
?>
