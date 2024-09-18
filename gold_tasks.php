<?php

require('./configs/config.php');
require_once('./api/PwAPI.php');

$api = new API();

function insertGold($line = null)
{
    global $config;
    global $api;

    echo "insertGold called with line: $line\n"; // log

    $conn = new mysqli($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['password'], $config['mysql']['db']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (strpos($line, "DeliverByAwardData: success = 1") !== false) {
        preg_match('/roleid=(\d+):taskid=(\d+):/', $line, $matches);
        $roleID = $matches[1];
        $missionID = $matches[2];

        // Task ID success for gaining gold
        $missionToMonitor = $config['missionToMonitor'];

        if ($missionID == $missionToMonitor) {
 
            $query = $conn->prepare("SELECT COUNT(*) as count FROM rewards_log WHERE role_id = ? AND mission_id = ?");
            $query->bind_param("ii", $roleID, $missionID);
            $query->execute();
            $result = $query->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                $idAcc = $api->getRoleBase($roleID);
                $userId = $idAcc['userid'];
                $roleName = $idAcc['name'];

                $din = $config['goldAmount'] * 100;
                
                echo "Calling chatInGame for $roleName\n"; // log
                $api->chatInGame("$roleName won $din gold's because he completed the mission $missionID successfully!");

                echo "Gold sent to user $userId\n"; // log
                $api->sendGold($userId, $din);

                $query = $conn->prepare("INSERT INTO rewards_log (role_id, mission_id) VALUES (?, ?)");
                $query->bind_param("ii", $roleID, $missionID);
                $query->execute();
            } else {
                echo "Reward already given for mission $missionID\n"; // log
            }

            $query->close();
        }
    }

    $conn->close();
}

if (isset($argv[1])) {
    insertGold($argv[1]);
} else {
    echo "No line provided to insertGold function.\n";
}
