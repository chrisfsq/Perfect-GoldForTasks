<?php

require('./configs/config.php');
require_once('./api/PwAPI.php');

$api = new API();

function insertGold($line = null)
{
    global $config;
    global $api;

    echo "insertGold called with line: $line\n"; // log para depuração

    $conn = new mysqli($config['mysql']['host'], $config['mysql']['user'], $config['mysql']['password'], $config['mysql']['db']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (strpos($line, "DeliverByAwardData: success = 1") !== false) {
        preg_match('/roleid=(\d+):taskid=(\d+):/', $line, $matches);
        $roleID = $matches[1];
        $missionID = $matches[2];

        // ID da tasks que será monitorada
        $missionToMonitor = 33469;

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

                $din = 1000 * 100; //quantidade de gold a ser enviada, muda só o primeiro valor "1000"
                
                echo "Calling chatInGame for $roleName\n"; // log para depuração
                $api->chatInGame("$roleName won $din gold's because he completed the mission $missionID successfully!");

                echo "Gold sent to user $userId\n"; // log para depuração
                $api->sendGold($userId, $din);

                $query = $conn->prepare("INSERT INTO rewards_log (role_id, mission_id) VALUES (?, ?)");
                $query->bind_param("ii", $roleID, $missionID);
                $query->execute();
            } else {
                echo "Reward already given for mission $missionID\n"; // log para depuração
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