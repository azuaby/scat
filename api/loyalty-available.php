<?
include '../scat.php';
include '../lib/person.php';

if (!defined('LOYALTY')) exit;

$person_id= (int)$_REQUEST['person'];

$person= person_load($db, $person_id);
if (!$person) {
  die_jsonp("No such person.");
}

$q= "SELECT item_id, cost, code, name, retail_price
       FROM loyalty_reward
       JOIN item ON item.id = item_id
      WHERE cost <= {$person['points_available']}
      ORDER BY cost DESC";

$r= $db->query($q)
  or die_query($db, $q);

$rewards= array();
while ($row= $r->fetch_assoc()) {
  $rewards[]= $row;
}

echo jsonp(array("rewards" => $rewards));