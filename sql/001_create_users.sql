CREATE TABLE IF NOT EXISTS Users (
  -- id is the stable identifier for one row.
  id INT AUTO_INCREMENT,
  modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  email VARCHAR(100) NOT NULL UNIQUE,
  -- Store a bcrypt password hash, never the original password.
  password_hash VARCHAR(60) NOT NULL,
  PRIMARY KEY (id)
);
