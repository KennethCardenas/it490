-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100),
    password VARCHAR(255),
    role ENUM('sitter', 'owner') NOT NULL
);

-- Table: sitters
CREATE TABLE sitters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    availability VARCHAR(255),
    rate VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: owners
CREATE TABLE owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_info TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: dogs
CREATE TABLE dogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(100),
    breed VARCHAR(100),
    age INT,
    care_instructions TEXT,
    FOREIGN KEY (owner_id) REFERENCES owners(id)
);

-- Table: sitter_dogs (active dog list for sitters)
CREATE TABLE sitter_dogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitter_id INT NOT NULL,
    dog_id INT NOT NULL,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (sitter_id) REFERENCES sitters(id),
    FOREIGN KEY (dog_id) REFERENCES dogs(id)
);

-- Table: activity_logs
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitter_id INT NOT NULL,
    dog_id INT NOT NULL,
    activity_type VARCHAR(50),
    notes TEXT,
    activity_date DATE,
    FOREIGN KEY (sitter_id) REFERENCES sitters(id),
    FOREIGN KEY (dog_id) REFERENCES dogs(id)
);