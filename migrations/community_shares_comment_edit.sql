-- Community shares table (per-user share tracking)
CREATE TABLE IF NOT EXISTS community_feed_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    UNIQUE KEY uniq_share_user_post (user_id, post_id),
    CONSTRAINT fk_share_post FOREIGN KEY (post_id) REFERENCES community_feed_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add updated_at to comments for edit tracking
ALTER TABLE community_feed_comments ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';
