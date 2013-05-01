<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\system\WCF;
use wcf\data\user\UserEditor;

/**
 * Removes users from temporary groups. 
 * 
 * @author      Joshua Rüsweg
 * @package	de.joshsboard.tempusergroup
 * @subpackage	system.cronjob
 * @category	Community Framework
 */
class RemoveUserFromGoupCronjob extends AbstractCronjob {
	/**
	 * @see	wcf\system\cronjob\ICronjob::execute()
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		$sql = "SELECT	userID, groupID
			FROM	wcf".WCF_N."_user_to_group_temp
			WHERE until < ".TIME_NOW;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();

		while ($row = $statement->fetchArray()) {
			$user = new UserEditor($row['userID']);
			$user->removeFromGroups(array($row['groupID']));
			$user->resetCache();
                        
                        $sql = "DELETE FROM wcf".WCF_N."_user_to_group_temp
                            WHERE 
                            userID = ? AND groupID = ?";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute(array($row['userID'], $row['groupID']));
		}
	}
}