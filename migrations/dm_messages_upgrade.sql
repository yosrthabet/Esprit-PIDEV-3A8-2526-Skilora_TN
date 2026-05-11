-- DmMessage upgrade: voice/image types, read receipts, timestamps
-- Run against skilorafinal database

ALTER TABLE dm_messages
    ADD COLUMN IF NOT EXISTS message_type VARCHAR(10) NOT NULL DEFAULT 'text',
    ADD COLUMN IF NOT EXISTS voice_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS read_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)';

-- Support ticket attachments (ensure table exists)
CREATE TABLE IF NOT EXISTS support_ticket_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    file_size INT NOT NULL DEFAULT 0,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)',
    CONSTRAINT fk_sta_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    CONSTRAINT fk_sta_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
