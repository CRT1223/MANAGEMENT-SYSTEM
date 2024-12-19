<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$actionType = $data['actionType'];

if ($actionType === "getEventsByDate") {
    $selectedDate = $data['selectedDate'];

    // Modify query to join TEAM table for both TEAM_1 and TEAM_2
    $query = "
        SELECT 
            SCHED_ID, 
            EVENT_NAME, 
            SCHED_TIME, 
            T1.TEAM_NAME AS TEAM_1_NAME, 
            T2.TEAM_NAME AS TEAM_2_NAME
        FROM 
            SCHEDULE S
        LEFT JOIN 
            TEAM T1 ON S.TEAM_1 = T1.TEAM_ID
        LEFT JOIN 
            TEAM T2 ON S.TEAM_2 = T2.TEAM_ID
        WHERE 
            SCHED_DATE = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "schedId" => $row['SCHED_ID'],
            "eventName" => $row['EVENT_NAME'],
            "time" => $row['SCHED_TIME'],
            "team1" => $row['TEAM_1_NAME'], 
            "team2" => $row['TEAM_2_NAME'],  
        ];
    }

    echo json_encode(["status" => "success", "events" => $events]);
} elseif ($actionType === "getUpcomingEvents") {
    $currentDate = $data['currentDate'];

    $query = "
        SELECT 
            SCHED_ID,
            EVENT_NAME, 
            SCHED_DATE, 
            SCHED_TIME, 
            T1.TEAM_NAME AS TEAM_1_NAME, 
            T2.TEAM_NAME AS TEAM_2_NAME
        FROM 
            SCHEDULE S
        LEFT JOIN 
            TEAM T1 ON S.TEAM_1 = T1.TEAM_ID
        LEFT JOIN 
            TEAM T2 ON S.TEAM_2 = T2.TEAM_ID
        WHERE 
            SCHED_DATE > ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "schedId" => $row['SCHED_ID'],
            "eventName" => $row['EVENT_NAME'],
            "date" => $row['SCHED_DATE'],
            "time" => $row['SCHED_TIME'],
            "team1" => $row['TEAM_1_NAME'],  
            "team2" => $row['TEAM_2_NAME'],  
        ];
    }

    echo json_encode(["status" => "success", "events" => $events]);
} elseif ($actionType === "getTeams") {
    // Get the list of teams
    $query = "SELECT TEAM_ID, TEAM_NAME FROM TEAM";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $teams = [];
        while ($row = $result->fetch_assoc()) {
            $teams[] = [
                "teamId" => $row['TEAM_ID'],  // Include the team ID
                "teamName" => $row['TEAM_NAME']  // Include the team name
            ];
        }

        // Return the teams as an array in JSON format
        $response = [
            "status" => "success",
            "teams" => $teams
        ];
        echo json_encode($response);
    } else {
        echo json_encode(["status" => "error", "message" => "No teams found"]);
    }
} elseif ($actionType === "getEventDates") {
    $query = "SELECT DISTINCT SCHED_DATE FROM SCHEDULE";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $dates = [];
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['SCHED_DATE'];
        }

        echo json_encode(["status" => "success", "dates" => $dates]);
    } else {
        echo json_encode(["status" => "error", "message" => "No dates found"]);
    }
} elseif ($actionType === "addEvent") {
    $eventTitle = $data['eventTitle'];
    $eventTime = $data['eventTime'];
    $eventDate = $data['eventDate'];
    $team1 = $data['team1'];
    $team2 = $data['team2'];

    if (!empty($eventTitle) && !empty($eventTime) && !empty($eventDate) && !empty($team1) && !empty($team2)) {
        $query = "INSERT INTO SCHEDULE (EVENT_NAME, SCHED_DATE, SCHED_TIME, TEAM_1, TEAM_2) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $eventTitle, $eventDate, $eventTime, $team1, $team2);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Event added successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add event"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete event data"]);
    }
} elseif ($actionType === "updateEvent") {
    $eventId = $data['eventId'];
    $eventTitle = $data['eventTitle'];
    $eventTime = $data['eventTime'];
    $team1 = $data['team1'];
    $team2 = $data['team2'];
    $selectedDate = $data['selectedDate'];

    // Update the event in the database
    $query = "
        UPDATE SCHEDULE 
        SET 
            EVENT_NAME = ?, 
            SCHED_TIME = ?, 
            TEAM_1 = ?,
            TEAM_2 = ?,
            SCHED_DATE = ?
        WHERE SCHED_ID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssi", $eventTitle, $eventTime, $team1, $team2, $selectedDate, $eventId);
    
    if ($stmt->execute()) {
        // Return the updated event details
        $updatedEvent = [
            "eventId" => $eventId,
            "eventName" => $eventTitle,
            "time" => $eventTime
        ];
        
        echo json_encode(["status" => "success", "updatedEvent" => $updatedEvent]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update event"]);
    }
} elseif ($actionType === "deleteEvent") {
    $eventId = $data['eventId'];

    // Delete the event from the database
    $query = "DELETE FROM SCHEDULE WHERE SCHED_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $eventId);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete event"]);
    }
} elseif ($actionType === "getEventDetails") {
    $eventId = $data['eventId'];  // Get the eventId passed from the frontend

    // Prepare the query to get the event details by event ID
    $query = "
        SELECT 
            SCHED_ID, EVENT_NAME, SCHED_TIME, 
            TEAM_1, TEAM_2,
            T1.TEAM_NAME AS TEAM_1_NAME, 
            T2.TEAM_NAME AS TEAM_2_NAME, 
            SCHED_DATE
        FROM SCHEDULE 
        LEFT JOIN TEAM AS T1 ON SCHEDULE.TEAM_1 = T1.TEAM_ID
        LEFT JOIN TEAM AS T2 ON SCHEDULE.TEAM_2 = T2.TEAM_ID
        WHERE SCHED_ID = ?";
    
    // Prepare the statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        
        // Respond with the event details
        echo json_encode([
            "status" => "success",
            "event" => [
                "eventId" => $event['SCHED_ID'],
                "eventName" => $event['EVENT_NAME'],
                "time" => $event['SCHED_TIME'],
                "team1id" => $event['TEAM_1'],
                "team1" => $event['TEAM_1_NAME'],
                "team2id" => $event['TEAM_2'],
                "team2" => $event['TEAM_2_NAME'],
                "date" => $event['SCHED_DATE']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Event not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action type"]);
}

$conn->close();
?>
