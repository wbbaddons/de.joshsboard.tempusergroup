<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\User;
use wcf\data\user\UserEditor;
use wcf\system\event\EventHandler;
use wcf\system\WCF;

/**
 * Removes users from temporary groups. 
 * 
 * @author      Joshua Ruesweg
 * @package	de.joshsboard.tempusergroup
 * @subpackage	system.cronjob
 * @category	Community Framework
 */
class RemoveUserFromGroupCronjob extends AbstractCronjob {
	/**
	 * @see	wcf\system\cronjob\ICronjob::execute()
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		// fetch data
		$users = array();
		$sql = "SELECT	userID, groupID
			FROM	wcf".WCF_N."_user_to_group_temp
			WHERE	until < ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(TIME_NOW));
		
		while ($row = $statement->fetchArray()) {
			if (!isset($users[$row['userID']])) {
				$users[$row['userID']] = array();
			}
			
			$users[$row['userID']][] = $row['groupID'];
		}
		
		if (count($users) == 0) return; 
		
		// remove users from groups
		$userObjects = User::getUsers(array_keys($users));
		foreach ($users as $userID => $groupIDs) {
			$user = $userObjects[$userID];
			$editor = new UserEditor($user);
			$editor->removeFromGroups($groupIDs);
		}
		
		$sql = "DELETE FROM	wcf".WCF_N."_user_to_group_temp
			WHERE		until < ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(TIME_NOW));
		
		// reset cache
		UserEditor::resetCache();
		
		EventHandler::getInstance()->fireAction($this, 'executed');
	}
}
