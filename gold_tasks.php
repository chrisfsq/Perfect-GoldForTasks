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
        echo "Connection failed: " . $conn->connect_error . "\n";
        return;
    }

    if (strpos($line, "DeliverByAwardData: success = 1") !== false) {
        preg_match('/roleid=(\d+):taskid=(\d+):/', $line, $matches);

        if (isset($matches[1]) && isset($matches[2])) {
            $roleID = $matches[1];
            $missionID = $matches[2];
        } else {
            echo "Failed to extract roleID or missionID from the line.\n";
            return;
        }

        $missionsGoldMap = $config['missionsGoldMap'];

        if (array_key_exists($missionID, $missionsGoldMap)) {
            $idAcc = $api->getRoleBase($roleID);
            
            if (!$idAcc || !isset($idAcc['userid'], $idAcc['name'])) {
                echo "Failed to retrieve account information for roleID: $roleID.\n";
                return;
            }

            $userId = $idAcc['userid'];
            $roleName = $idAcc['name'];
            $goldAmount = $missionsGoldMap[$missionID] * 100;
            $goldToShow = $missionsGoldMap[$missionID];

            echo "Calling chatInGame for $roleName\n"; // log
            $api->chatInGame("$roleName won $goldToShow gold's because he completed the mission $missionID successfully!");

            echo "Gold sent to user $userId\n"; // log
            //$api->sendGold($userId, $goldAmount);
            $stmt = $conn->prepare("CALL usecash(?, ?, ?, ?, ?, ?, ?, ?)");
            $param1 = 1;
            $param2 = 0;
            $param3 = 1;
            $param4 = 0;
            $param5 = 1;
            $error = 0;
            $stmt->bind_param("iiiiiiii", $userId, $param1, $param2, $param3, $param4, $goldAmount, $param5, $error);
            $stmt->execute();

            $stmt->close();

            $query = $conn->prepare("INSERT INTO rewards_log (role_id, mission_id) VALUES (?, ?)");
            $query->bind_param("ii", $roleID, $missionID);
            $query->execute();
            $query->close();
        } else {
            echo "Mission ID $missionID not configured for gold reward\n"; // log
        }
    }

    $conn->close();
}

if (isset($argv[1])) {
    insertGold($argv[1]);
} else {
    echo "No line provided to insertGold function.\n";
}
