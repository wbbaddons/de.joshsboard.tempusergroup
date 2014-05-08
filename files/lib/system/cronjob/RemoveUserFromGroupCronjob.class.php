<?php
namespace wcf\system\cronjob;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\User;
use wcf\data\user\UserEditor;
use wcf\data\user\UserProfileAction;
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
	 * all user which are updated
	 * @var array<User> 
	 **/
	public $user = array(); 
	
	/**
	 * all user which are updated
	 * @var array<mixed> 
	 **/
	public $userToGroups = array(); 
	
	/**
	 * @see	wcf\system\cronjob\ICronjob::execute()
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		// fetch data
		$sql = "SELECT	userID, groupID
			FROM	wcf".WCF_N."_user_to_group_temp
			WHERE	until < ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(TIME_NOW));
		
		while ($row = $statement->fetchArray()) {
			if (!isset($this->userToGroups[$row['userID']])) {
				$this->userToGroups[$row['userID']] = array();
			}
			
			$this->userToGroups[$row['userID']][] = $row['groupID'];
		}
		
		if (count($this->userToGroups) != 0) {
			// remove users from groups
			$userObjects = User::getUsers(array_keys($this->userToGroups));
			foreach ($this->userToGroups as $userID => $groupIDs) {
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
			
			// reread the user
			$this->user = User::getUsers(array_keys($this->userToGroups));
			
			$editor = array(); 
			
			foreach ($this->user as $user) {
				$editor[] = new UserEditor($user); 
			}
			
			// update user ranks
			if (MODULE_USER_RANK) {
			        $action = new UserProfileAction($editor, 'updateUserRank');
			        $action->executeAction();
			}
			
			if (MODULE_USERS_ONLINE) {
			        $action = new UserProfileAction($editor, 'updateUserOnlineMarking');
			        $action->executeAction();
			} 
		}
		
		EventHandler::getInstance()->fireAction($this, 'executed');
	}
}
