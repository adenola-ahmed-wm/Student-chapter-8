<?php
// Start the session
require_once('startsession.php');

// Insert the page header
$page_title = 'Questionnaire';
require_once('header.php');

require_once('appvars.php');
require_once('connectvars.php');

// Make sure the user is logged in before going any further.
if (!isset($_SESSION['user_id'])) {
    echo '<p class="login">Please <a href="login.php">log in</a> to access this page.</p>';
    exit();
}

// Show the navigation menu
require_once('navmenu.php');

// Connect to the database
$dbh = new PDO('mysql:host=localhost;dbname=mismatchdb', 'root', 'root');

// If this user has never answered the questionnaire, insert empty responses into the database
$query = "SELECT * FROM mismatch_response WHERE user_id = '" . $_SESSION['user_id'] . "'";
$stmt = $dbh->prepare($query);
$stmt->execute();

$results = $stmt->fetchAll();

if (count($results) == 0) {
    // First grab the list of topic IDs from the topic table
    $query = "SELECT topic_id FROM mismatch_topic ORDER BY category";
    $stmt = $dbh->prepare($query);
    $stmt->execute();

    $results = $stmt->fetchAll();
    $topicIDs = array();

    foreach ($results as $row) {
        array_push($topicIDs, $row['topic_id']);
    }

    // Insert empty response rows into the response table, one per topic
    foreach ($topicIDs as $topic_id) {
        echo $topic_id;
        $query = "INSERT INTO mismatch_response (user_id, topic_id) VALUES ('" . $_SESSION['user_id'] . "', :topic_id)";
        $stmt = $dbh->prepare($query);
        $stmt->execute(array('topic_id'=>$topic_id));

    }
}

// If the questionnaire form has been submitted, write the form responses to the database
if (isset($_POST['submit'])) {
    // Write the questionnaire response rows to the response table
    foreach ($_POST as $response_id => $response) {
        $query = "UPDATE mismatch_response SET response = :response WHERE response_id = :response_id";
        $stmt = $dbh->prepare($query);
        $stmt->execute(array("response" => $response, "response_id" => $response_id));

    }
    echo '<p>Your responses have been saved.</p>';
}

// Grab the response data from the database to generate the form
$query = "SELECT response_id, topic_id, response FROM mismatch_response WHERE user_id = '" . $_SESSION['user_id'] . "'";
$stmt = $dbh->prepare($query);
$stmt->execute();
$responses = array();
$results = $stmt->fetchAll();

foreach ($results as $row) {

    // Look up the topic name for the response from the topic table
    $query2 = "SELECT name, category FROM mismatch_topic WHERE topic_id = :topic_id";
    $stmt2 = $dbh->prepare($query2);

    $result = $stmt2->execute(array('topic_id'=>$row['topic_id']));

    $responses = array();
    $results2 = $stmt2->fetchAll();

    if (count($results2) == 1) {
        $row2 = $results2[0];
        $row['topic_name'] = $row2['name'];
        $row['category_name'] = $row2['category'];
        array_push($responses, $row);
    }
}


// Generate the questionnaire form by looping through the response array
echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
echo '<p>How do you feel about each topic?</p>';
$category = $responses[0]['category_name'];
echo '<fieldset><legend>' . $responses[0]['category_name'] . '</legend>';
foreach ($responses as $response) {
    // Only start a new fieldset if the category has changed
    if ($category != $response['category_name']) {
        $category = $response['category_name'];
        echo '</fieldset><fieldset><legend>' . $response['category_name'] . '</legend>';
    }

    // Display the topic form field
    echo '<label ' . ($response['response'] == NULL ? 'class="error"' : '') . ' for="' . $response['response_id'] . '">' . $response['topic_name'] . ':</label>';
    echo '<input type="radio" id="' . $response['response_id'] . '" name="' . $response['response_id'] . '" value="1" ' . ($response['response'] == 1 ? 'checked="checked"' : '') . ' />Love ';
    echo '<input type="radio" id="' . $response['response_id'] . '" name="' . $response['response_id'] . '" value="2" ' . ($response['response'] == 2 ? 'checked="checked"' : '') . ' />Hate<br />';
}
echo '</fieldset>';
echo '<input type="submit" value="Save Questionnaire" name="submit" />';
echo '</form>';

// Insert the page footer
require_once('footer.php');
?>
