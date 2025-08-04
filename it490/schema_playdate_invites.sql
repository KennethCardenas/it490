CREATE TABLE PLAYDATE_INVITES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inviter_id INT NOT NULL,
    invitee_id INT NOT NULL,
    playdate_id INT NOT NULL,
    message VARCHAR(255) DEFAULT '',
    status ENUM('pending','accepted','declined') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    FOREIGN KEY (inviter_id) REFERENCES USERS(id),
    FOREIGN KEY (invitee_id) REFERENCES USERS(id),
    FOREIGN KEY (playdate_id) REFERENCES PLAYDATES(id)
);
