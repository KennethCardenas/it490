-- 1. Extend DOGS
ALTER TABLE DOGS ADD COLUMN care_instructions TEXT;

-- 2. SITTERS Table
CREATE TABLE IF NOT EXISTS SITTERS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    experience_years INT DEFAULT 0,
    rating FLOAT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);

-- 3. DOG_ACCESS
CREATE TABLE IF NOT EXISTS DOG_ACCESS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dog_id INT NOT NULL,
    sitter_id INT NOT NULL,
    access_level ENUM('viewer', 'logger', 'medical') DEFAULT 'viewer',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dog_id) REFERENCES DOGS(id),
    FOREIGN KEY (sitter_id) REFERENCES SITTERS(id)
);

-- 4. Expand ACTIVITIES for Behavior
ALTER TABLE ACTIVITIES
ADD COLUMN mood VARCHAR(32),
ADD COLUMN intensity TINYINT,
ADD COLUMN trigger_text TEXT;

-- 5. Optional: User avatars
ALTER TABLE USERS ADD COLUMN profile_image_url VARCHAR(255) DEFAULT NULL;

-- 6. Create LOGS table for centralized logging
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    type VARCHAR(50),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(id)
);
