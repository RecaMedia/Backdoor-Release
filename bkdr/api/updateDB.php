<?php
$settingsQuery = $this->db->prepare("UPDATE Settings SET SettingValue=:value WHERE SettingType=:type");
$settingsQuery->bindValue(':value','v2.0.6');
$settingsQuery->bindValue(':type','bkdr_version');
$result = $settingsQuery->execute();

if ($result) {
  $process_msg[] = "Database successfully updated.";
} else {
  $process_msg[] = "There was an error updating the database.";
}
?>