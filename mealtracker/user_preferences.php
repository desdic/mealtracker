<?php

function getUserPreferences($pdo, $userid) {
	$stmt = $pdo->prepare("SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?");
	$stmt->execute([$userid]);
	$preferences = [];
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$preferences[$row['preference_key']] = $row['preference_value'];
	}

	if (!isset($preferences['dateformat'])) {
		$preferences['dateformat'] = 'd/m/Y';
	}

	if (!isset($preferences['theme'])) {
		$preferences['theme'] = 'light';
	}

	return $preferences;
}

?>
