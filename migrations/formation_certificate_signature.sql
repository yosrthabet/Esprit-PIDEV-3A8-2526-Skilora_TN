-- Add certificate signature columns to formations table
ALTER TABLE formations ADD COLUMN IF NOT EXISTS director_signature LONGTEXT DEFAULT NULL;
ALTER TABLE formations ADD COLUMN IF NOT EXISTS certificate_signature_filename VARCHAR(255) DEFAULT NULL;

-- Add AI review columns to formations table
ALTER TABLE formations ADD COLUMN IF NOT EXISTS review_note LONGTEXT DEFAULT NULL;
ALTER TABLE formations ADD COLUMN IF NOT EXISTS review_score DECIMAL(5,2) DEFAULT NULL;
