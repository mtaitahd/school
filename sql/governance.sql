-- Governance & Leadership table
-- Run this manually or visit any admin dashboard page to auto-create

CREATE TABLE IF NOT EXISTS governance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    image_path VARCHAR(500) NULL,
    profile_link VARCHAR(500) NULL,
    border_color VARCHAR(50) DEFAULT 'blue',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data (optional)
INSERT INTO governance (name, title, image_path, profile_link, border_color, sort_order) VALUES
('Prof. John G. Safari', 'Vice Chancellor', NULL, 'https://example.com/prof-safari', 'blue', 1),
('Dr. Anna S. Makinda', 'Deputy Vice Chancellor', NULL, 'https://example.com/dr-makinda', 'green', 2),
('Mr. David Mwenda', 'Registrar', NULL, 'https://example.com/mr-mwenda', 'red', 3),
('Eng. Sarah Lema', 'Dean of Students', NULL, 'https://example.com/eng-lema', 'yellow', 4),
('Dr. Peter Kilonzo', 'Head of Research', NULL, 'https://example.com/dr-kilonzo', 'purple', 5);
