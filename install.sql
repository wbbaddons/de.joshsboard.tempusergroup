DROP TABLE wcf1_user_to_group_temp;
CREATE TABLE wcf1_user_to_group_temp (
	userID		INT(10) NOT NULL,
	groupID		INT(10) NOT NULL,
	until		INT(10) NOT NULL,
	
	KEY (userID),
	KEY (groupID)
);

ALTER TABLE wcf1_user_to_group_temp ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE;
ALTER TABLE wcf1_user_to_group_temp ADD FOREIGN KEY (groupID) REFERENCES wcf1_user_group (groupID) ON DELETE CASCADE;